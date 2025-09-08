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
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\Worker;

final readonly class EventSubjectToNotificationAsynchronousQueueDispatcher implements EventDispatcherInterface
{
    public const TOPIC = 'tuleap.pullrequest.notification';

    public function __construct(private QueueFactory $queue_factory)
    {
    }

    #[\Override]
    public function dispatch(object $event): object
    {
        if (! $event instanceof EventSubjectToNotification) {
            return $event;
        }

        $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME);
        $queue->pushSinglePersistentMessage(
            self::TOPIC,
            [
                'event_class' => $event::class,
                'content'     => $event->toWorkerEventPayload(),
            ]
        );

        return $event;
    }
}
