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

namespace Tuleap\PullRequest\Reviewer\Notification;

use Psr\EventDispatcher\ListenerProviderInterface;
use Tuleap\PullRequest\Notification\Strategy\PullRequestNotificationStrategy;
use Tuleap\PullRequest\Notification\EventSubjectToNotification;
use Tuleap\PullRequest\Notification\EventSubjectToNotificationListener;
use Tuleap\PullRequest\Notification\EventSubjectToNotificationListenerProvider;
use Tuleap\PullRequest\Notification\EventSubjectToNotificationSynchronousDispatcher;
use Tuleap\PullRequest\Notification\NotificationToProcess;
use Tuleap\PullRequest\Notification\NotificationToProcessBuilder;

final class EventSubjectToNotificationSynchronousDispatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testEventNotificationsAreDispatched(): void
    {
        $event = $this->createMock(EventSubjectToNotification::class);

        $strategy_1 = $this->createMock(PullRequestNotificationStrategy::class);
        $builder_1  = $this->createMock(NotificationToProcessBuilder::class);
        $listener_1 = new EventSubjectToNotificationListener($strategy_1, $builder_1);
        $strategy_2 = $this->createMock(PullRequestNotificationStrategy::class);
        $builder_2  = $this->createMock(NotificationToProcessBuilder::class);
        $listener_2 = new EventSubjectToNotificationListener($strategy_2, $builder_2);

        $dispatcher = new EventSubjectToNotificationSynchronousDispatcher(
            new EventSubjectToNotificationListenerProvider([
                $event::class => [
                    static function () use ($listener_1): EventSubjectToNotificationListener {
                        return $listener_1;
                    },
                    static function () use ($listener_2): EventSubjectToNotificationListener {
                        return $listener_2;
                    },
                ],
            ])
        );

        $builder_1->method('getNotificationsToProcess')->willReturn([
            $this->createMock(NotificationToProcess::class),
            $this->createMock(NotificationToProcess::class),
        ]);
        $strategy_1->expects(self::exactly(2))->method('execute');
        $builder_2->method('getNotificationsToProcess')->willReturn([]);
        $strategy_2->expects(self::never())->method('execute');

        self::assertSame($event, $dispatcher->dispatch($event));
    }

    public function testNothingHappensWhenNoListenerRespondsToTheDispatchedEvent(): void
    {
        $dispatcher = new EventSubjectToNotificationSynchronousDispatcher(
            new EventSubjectToNotificationListenerProvider([])
        );

        $event = $this->createMock(EventSubjectToNotification::class);

        self::assertSame($event, $dispatcher->dispatch($event));
    }

    public function testNothingHappenWhenProcessingSomethingThatIsNotAnEventSubjectToNotification(): void
    {
        $dispatcher = new EventSubjectToNotificationSynchronousDispatcher($this->createMock(ListenerProviderInterface::class));

        $event = new \stdClass();

        self::assertSame($event, $dispatcher->dispatch($event));
    }
}
