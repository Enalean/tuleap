<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Notification;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

final class EventDispatcherWithFallbackTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDoNotDispatchOnSecondaryDispatcherIfTheFirstDispatcherSucceeds(): void
    {
        $dispatcher_with_fallback = new EventDispatcherWithFallback(
            new \Psr\Log\NullLogger(),
            new class implements EventDispatcherInterface {
                public function dispatch(object $event): object
                {
                    return $event;
                }
            },
            new class implements EventDispatcherInterface {
                public function dispatch(object $event): object
                {
                    throw new \RuntimeException('Should not be called since first dispatcher succeeds');
                }
            }
        );

        $event = new class
        {
        };

        $this->assertSame($event, $dispatcher_with_fallback->dispatch($event));
    }

    public function testEventGetsDispatchedToSecondaryDispatcherWhenTheFirstFails(): void
    {
        $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);

        $dispatcher_with_fallback = new EventDispatcherWithFallback(
            $logger,
            new class implements EventDispatcherInterface {
                public function dispatch(object $event): object
                {
                    throw new \RuntimeException('Failure');
                }
            },
            new class ($this) implements EventDispatcherInterface {
                /**
                 * @var TestCase
                 */
                private $test;

                public function __construct(TestCase $test)
                {
                    $this->test = $test;
                }

                public function dispatch(object $event): object
                {
                    $this->test->addToAssertionCount(1);
                    return $event;
                }
            }
        );

        $event = new class
        {
        };

        $logger->shouldReceive('debug')->once();
        $this->assertSame($event, $dispatcher_with_fallback->dispatch($event));
        $this->assertEquals(1, $this->getNumAssertions());
    }
}
