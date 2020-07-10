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
use Tuleap\Queue\NoQueueSystemAvailableException;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\Worker;

final class EventSubjectToNotificationAsynchronousRedisDispatcher implements EventDispatcherInterface
{
    public const TOPIC = 'tuleap.pullrequest.notification';

    /**
     * @var QueueFactory
     */
    private $queue_factory;

    public function __construct(QueueFactory $queue_factory)
    {
        $this->queue_factory = $queue_factory;
    }

    /**
     * @throws NoQueueSystemAvailableException
     */
    public function dispatch(object $event): object
    {
        if (! $event instanceof EventSubjectToNotification) {
            return $event;
        }

        if (\ForgeConfig::getInt('sys_nb_backend_workers') <= 0) {
            throw new NoWorkerAvailableToProcessTheQueueException();
        }

        $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME, QueueFactory::REDIS);
        $queue->pushSinglePersistentMessage(
            self::TOPIC,
            [
                'event_class' => get_class($event),
                'content'     => $event->toWorkerEventPayload(),
            ]
        );

        return $event;
    }
}
