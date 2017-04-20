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

namespace Tuleap\Svn\Logs;

use Tuleap\Queue\Factory;
use Tuleap\Httpd\PostRotateEvent;
use Logger;
use WrapperLogger;
use UserManager;

class ParseQueue
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var \Tuleap\Queue\PersistentQueue
     */
    private $queue;

    public function __construct(Logger $logger)
    {
        $this->logger = new WrapperLogger($logger, 'svn.log');
        $this->queue  = Factory::getPersistentQueue($this->logger, PostRotateEvent::QUEUE_PREFIX);
    }

    public function listen()
    {
        $this->logger->info("Wait for messages");

        $logger = $this->logger;
        $writer = new DBWriter(new DBWriterDao(), UserManager::instance());

        $this->queue->listen(PostRotateEvent::QUEUE_PREFIX, PostRotateEvent::TOPIC, function ($msg) use ($logger, $writer) {
            try {
                $logger->info("Received: ".$msg->body);

                $writer->saveFromFile('/var/log/httpd/svn_log.1');

                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                $logger->info("Parse completed");
            } catch (Exception $e) {
                $logger->error("Caught exception ".get_class($e).": ".$e->getMessage());
            }
        });
    }
}
