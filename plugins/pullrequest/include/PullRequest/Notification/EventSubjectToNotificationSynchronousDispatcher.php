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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

final class EventSubjectToNotificationSynchronousDispatcher implements EventDispatcherInterface
{
    /**
     * @var ListenerProviderInterface
     */
    private $listener_provider;
    /**
     * @var PullRequestNotificationExecutor
     */
    private $executor;

    public function __construct(ListenerProviderInterface $listener_provider, PullRequestNotificationExecutor $executor)
    {
        $this->listener_provider = $listener_provider;
        $this->executor          = $executor;
    }

    public function dispatch(object $event): object
    {
        if (! $event instanceof EventSubjectToNotification) {
            return $event;
        }

        foreach ($this->listener_provider->getListenersForEvent($event) as $listener_callable) {
            $notification_to_process_builder = $listener_callable();
            // This is implied because are supposed to provide callable compatible with the event type
            assert($notification_to_process_builder instanceof NotificationToProcessBuilder);

            $this->processNotifications(
                ...$notification_to_process_builder->getNotificationsToProcess($event)
            );
        }

        return $event;
    }

    private function processNotifications(NotificationToProcess ...$notifications): void
    {
        foreach ($notifications as $notification) {
            $this->executor->execute($notification);
        }
    }
}
