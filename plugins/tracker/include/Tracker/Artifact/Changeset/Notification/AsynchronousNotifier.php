<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Artifact\Changeset\Notification;

use PhpAmqpLib\Message\AMQPMessage;
use Tracker_ArtifactFactory;
use Logger;
use Exception;
use Tuleap\Queue\RabbitMQ\ExchangeToExchangeBindings;
use Tuleap\System\DaemonLocker;
use Tuleap\Queue\WorkerGetQueue;

class AsynchronousNotifier
{
    const MAX_MESSAGES = 1000;

    const QUEUE_ID     = 'update_notify-1';

    const QUEUE_PREFIX = 'update';

    const TOPIC = 'tuleap.tracker.artifact';

    public function addListener(WorkerGetQueue $event)
    {
        $stuff_queue = new ExchangeToExchangeBindings($event->getChannel(), self::QUEUE_PREFIX);
        $stuff_queue->addListener(self::QUEUE_ID, self::TOPIC, $this->getCallback($event->getLogger(), $event->getLocker()));
    }

    private function getCallback(Logger $logger, DaemonLocker $locker)
    {
        $notifier = Notifier::build($logger);

        $message_counter = 0;

        return function (AMQPMessage $msg) use ($logger, &$message_counter, $notifier, $locker) {
            try {
                $logger->info("Received ".$msg->body);

                $message = json_decode($msg->body, true);
                $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($message['artifact_id']);
                $changeset = $artifact->getChangeset($message['changeset_id']);

                $notifier->processAsyncNotify($changeset);
                $message_counter++;

                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                $logger->info("Notification completed [$message_counter/".self::MAX_MESSAGES."]");

                if ($message_counter >= self::MAX_MESSAGES) {
                    $logger->info("Max messages reached, exiting...");
                    $locker->cleanExit();
                }
            } catch (Exception $e) {
                $logger->error("Caught exception ".get_class($e).": ".$e->getMessage());
            }
        };
    }
}
