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
use Tuleap\PullRequest\Notification\Strategy\PullRequestNotificationStrategy;

final class EventSubjectToNotificationSynchronousDispatcher implements EventDispatcherInterface
{
    /**
     * @var ListenerProviderInterface
     */
    private $listener_provider;

    public function __construct(ListenerProviderInterface $listener_provider)
    {
        $this->listener_provider = $listener_provider;
    }

    public function dispatch(object $event): object
    {
        if (! $event instanceof EventSubjectToNotification) {
            return $event;
        }

        foreach ($this->listener_provider->getListenersForEvent($event) as $listener_callable) {
            $listener = $listener_callable();
            // This is implied because are supposed to provide callable compatible with the event type
            assert($listener instanceof EventSubjectToNotificationListener);

            $this->processNotifications(
                $listener->getNotificationStrategy(),
                ...$listener->getNotificationToProcessBuilder()->getNotificationsToProcess($event)
            );
        }

        return $event;
    }

    private function processNotifications(PullRequestNotificationStrategy $strategy, NotificationToProcess ...$notifications): void
    {
        foreach ($notifications as $notification) {
            $strategy->execute($notification);
        }
    }
}
