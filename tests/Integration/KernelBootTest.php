<?php

declare(strict_types=1);

namespace Ledger\Tests\Integration;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

final class KernelBootTest extends KernelTestCase
{
    public function testTheContainerWiresTheBusAndDatabase(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertInstanceOf(MessageBusInterface::class, $container->get(MessageBusInterface::class));
        self::assertInstanceOf(Connection::class, $container->get(Connection::class));
    }
}
