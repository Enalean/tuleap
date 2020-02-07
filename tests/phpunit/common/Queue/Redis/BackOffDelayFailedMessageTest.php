<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Queue\Redis;

use PHPUnit\Framework\TestCase;

final class BackOffDelayFailedMessageTest extends TestCase
{
    /**
     * @dataProvider dataProviderAttempts
     */
    public function testCallsDelayFunctionReceiveExpectedValue(int $nb_time_message_has_been_queued, int $expected_delay): void
    {
        $backoff_delay = new BackOffDelayFailedMessage(
            new \Psr\Log\NullLogger(),
            function (int $time_to_sleep) use ($expected_delay): void {
                $this->assertEquals($expected_delay, $time_to_sleep);
            }
        );

        $backoff_delay->delay($this->buildMessage($nb_time_message_has_been_queued));
    }

    public function testDelayingAMessageThatHasNeverBeenQueuedIsNotAccepted(): void
    {
        $this->expectException(CannotDelayMessageThatHasNotBeenQueuedException::class);

        $backoff_delay = new BackOffDelayFailedMessage(
            new \Psr\Log\NullLogger(),
            static function (int $time_to_sleep): void {
                throw new \LogicException('Should not called');
            }
        );
        $backoff_delay->delay($this->buildMessage(0));
    }

    public function dataProviderAttempts(): array
    {
        return [
            [1, 5],
            [3, 20],
            [4, 40],
            'Max delay is capped' => [10, 40],
        ];
    }

    private function buildMessage(int $nb_time_message_has_been_queued): EventMessageForPersistentQueue
    {
        return new class($nb_time_message_has_been_queued) implements EventMessageForPersistentQueue
        {
            /**
             * @var int
             */
            private $nb_time_message_has_been_queued;

            public function __construct(int $nb_time_message_has_been_queued)
            {
                $this->nb_time_message_has_been_queued = $nb_time_message_has_been_queued;
            }

            public function getTopic() : string
            {
                return '';
            }

            public function getPayload(): string
            {
                return '';
            }

            public function getEnqueueTime(): float
            {
                return 0;
            }

            public function getNumberOfTimesMessageHasBeenQueued(): int
            {
                return $this->nb_time_message_has_been_queued;
            }
        };
    }
}
