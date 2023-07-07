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

use Tuleap\PullRequest\Notification\Strategy\PullRequestNotificationStrategy;

final class EventSubjectToNotificationListenerProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testFindsRegisteredListener(): void
    {
        $event_subject_notification_implementation = $this->createMock(EventSubjectToNotification::class);

        $listener_provider = new EventSubjectToNotificationListenerProvider([
            $event_subject_notification_implementation::class => [
                function (): EventSubjectToNotificationListener {
                    return new EventSubjectToNotificationListener(
                        $this->createMock(PullRequestNotificationStrategy::class),
                        $this->createMock(NotificationToProcessBuilder::class)
                    );
                },
                function (): EventSubjectToNotificationListener {
                    return new EventSubjectToNotificationListener(
                        $this->createMock(PullRequestNotificationStrategy::class),
                        $this->createMock(NotificationToProcessBuilder::class)
                    );
                },
            ],
        ]);

        $listeners = $listener_provider->getListenersForEvent($event_subject_notification_implementation);

        self::assertCount(2, $listeners);
    }

    public function testNoListenerAreFoundWhenNothingMatchesTheNotificationEvent(): void
    {
        $listener_provider = new EventSubjectToNotificationListenerProvider([]);

        $event_subject_to_notification = new class implements EventSubjectToNotification {
            public static function fromWorkerEventPayload(array $payload): EventSubjectToNotification
            {
                return new self();
            }

            public function toWorkerEventPayload(): array
            {
                return [];
            }
        };

        $listeners = $listener_provider->getListenersForEvent($event_subject_to_notification);

        self::assertEmpty($listeners);
    }
}
