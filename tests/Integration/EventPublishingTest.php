<?php

declare(strict_types=1);

namespace Ledger\Tests\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Ledger\Application\Wallet\Command\DepositFunds;
use Ledger\Application\Wallet\Command\OpenWallet;
use Ledger\Domain\Wallet\Event\FundsDeposited;
use Ledger\Domain\Wallet\Event\WalletOpened;
use Ledger\Domain\Wallet\WalletId;
use Ledger\Infrastructure\EventStore\EventStoreSchema;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class EventPublishingTest extends KernelTestCase
{
    public function testCommandsLeaveTheirEventsOnTheAsyncTransport(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        $connection = $container->get('doctrine.dbal.default_connection');
        self::assertInstanceOf(Connection::class, $connection);
        $this->resetStream($connection);

        $bus = $container->get(MessageBusInterface::class);
        self::assertInstanceOf(MessageBusInterface::class, $bus);

        $transport = $container->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $transport);

        $id = WalletId::generate();
        $bus->dispatch(new OpenWallet($id->value, 'SAR'));
        $bus->dispatch(new DepositFunds($id->value, 4200, 'SAR'));

        $sent = array_map(
            static fn ($envelope): object => $envelope->getMessage(),
            $transport->getSent(),
        );

        self::assertCount(2, $sent);
        self::assertInstanceOf(WalletOpened::class, $sent[0]);
        self::assertInstanceOf(FundsDeposited::class, $sent[1]);
        self::assertSame(4200, $sent[1]->minorUnits);

        $connection->executeStatement('DROP TABLE IF EXISTS '.EventStoreSchema::TABLE);
    }

    private function resetStream(Connection $connection): void
    {
        $connection->executeStatement('DROP TABLE IF EXISTS '.EventStoreSchema::TABLE);

        $schema = new Schema();
        EventStoreSchema::configure($schema);

        foreach ($schema->toSql($connection->getDatabasePlatform()) as $sql) {
            $connection->executeStatement($sql);
        }
    }
}
