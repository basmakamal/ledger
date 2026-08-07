<?php

declare(strict_types=1);

namespace Ledger\Tests\Unit\Infrastructure;

use Ledger\Application\Exception\ConcurrencyConflict;
use Ledger\Application\Wallet\Command\DepositFunds;
use Ledger\Infrastructure\Bus\MessengerCommandBus;
use Ledger\Tests\Support\ScriptedMessageBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class MessengerCommandBusTest extends TestCase
{
    public function testACommandThatWinsFirstTimeIsDispatchedOnce(): void
    {
        $inner = new ScriptedMessageBus();

        (new MessengerCommandBus($inner))->dispatch($this->command());

        self::assertSame(1, $inner->dispatches);
    }

    public function testALostRaceIsRetriedUntilItLands(): void
    {
        $inner = new ScriptedMessageBus([$this->conflict(), $this->conflict()]);

        (new MessengerCommandBus($inner))->dispatch($this->command());

        self::assertSame(3, $inner->dispatches);
    }

    public function testRetriesAreBounded(): void
    {
        $inner = new ScriptedMessageBus([$this->conflict(), $this->conflict(), $this->conflict()]);

        try {
            (new MessengerCommandBus($inner))->dispatch($this->command());
            self::fail('a permanently contended command must surface the conflict');
        } catch (HandlerFailedException) {
            self::assertSame(3, $inner->dispatches);
        }
    }

    public function testTheAttemptCountIsConfigurable(): void
    {
        $inner = new ScriptedMessageBus([$this->conflict(), $this->conflict(), $this->conflict(), $this->conflict()]);

        try {
            (new MessengerCommandBus($inner, 5))->dispatch($this->command());
        } catch (HandlerFailedException) {
            self::fail('five attempts should have outlasted four conflicts');
        }

        self::assertSame(5, $inner->dispatches);
    }

    public function testOtherFailuresAreNotRetried(): void
    {
        $inner = new ScriptedMessageBus([
            new HandlerFailedException(new Envelope($this->command()), [new \LogicException('broken handler')]),
        ]);

        $this->expectException(HandlerFailedException::class);

        try {
            (new MessengerCommandBus($inner))->dispatch($this->command());
        } finally {
            self::assertSame(1, $inner->dispatches);
        }
    }

    private function command(): DepositFunds
    {
        return new DepositFunds('11111111-1111-4111-8111-111111111111', 100, 'SAR');
    }

    private function conflict(): HandlerFailedException
    {
        return new HandlerFailedException(
            new Envelope($this->command()),
            [ConcurrencyConflict::for('11111111-1111-4111-8111-111111111111', 1)],
        );
    }
}
