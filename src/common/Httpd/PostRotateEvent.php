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

namespace Tuleap\Httpd;

use Tuleap\Queue\Factory;
use Logger;
use WrapperLogger;

/**
 * Event emitted after HTTP log rotation
 */
class PostRotateEvent
{
    const QUEUE_PREFIX = 'httpd_postrotate';

    const TOPIC        = 'tuleap.httpd.log';

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
        $this->logger = new WrapperLogger($logger, 'httpd.postrotate');
        $this->queue  = Factory::getPersistentQueue($this->logger, self::QUEUE_PREFIX);
    }

    public function push($arg)
    {
        $this->logger->info('Send message to '.self::TOPIC);
        $this->queue->pushSinglePersistentMessage(self::TOPIC, $arg);
        $this->logger->debug('Done');
    }
}
