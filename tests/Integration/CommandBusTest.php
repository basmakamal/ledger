<?php

declare(strict_types=1);

namespace Ledger\Tests\Integration;

use Ledger\Application\Wallet\Command\DepositFunds;
use Ledger\Application\Wallet\Command\OpenWallet;
use Ledger\Application\Wallet\Command\WithdrawFunds;
use Ledger\Domain\Wallet\WalletId;
use Ledger\Domain\Wallet\WalletRepository;
use Ledger\Infrastructure\EventStore\EventStoreSchema;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Proves the container really routes each command to its handler, which the
 * unit tests cannot see because they instantiate handlers directly.
 */
final class CommandBusTest extends KernelTestCase
{
    public function testCommandsFlowThroughTheBusOntoTheStream(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $connection = $container->get('doctrine.dbal.default_connection');
        $bus = $container->get(MessageBusInterface::class);
        $wallets = $container->get(WalletRepository::class);

        self::assertInstanceOf(\Doctrine\DBAL\Connection::class, $connection);
        self::assertInstanceOf(MessageBusInterface::class, $bus);
        self::assertInstanceOf(WalletRepository::class, $wallets);

        $connection->executeStatement('DROP TABLE IF EXISTS '.EventStoreSchema::TABLE);

        $schema = new \Doctrine\DBAL\Schema\Schema();
        EventStoreSchema::configure($schema);

        foreach ($schema->toSql($connection->getDatabasePlatform()) as $sql) {
            $connection->executeStatement($sql);
        }

        $id = WalletId::generate();

        $bus->dispatch(new OpenWallet($id->value, 'SAR'));
        $bus->dispatch(new DepositFunds($id->value, 12000, 'SAR'));
        $bus->dispatch(new WithdrawFunds($id->value, 2000, 'SAR'));

        $wallet = $wallets->get($id);

        self::assertSame(10000, $wallet->balance()->minorUnits);
        self::assertSame(3, $wallet->version());

        $connection->executeStatement('DROP TABLE IF EXISTS '.EventStoreSchema::TABLE);
    }
}
