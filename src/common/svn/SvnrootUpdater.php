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

namespace Tuleap\Svn;

use Backend;
use ForgeConfig;
use Logger;
use Tuleap\Queue\QueueFactory;
use Tuleap\System\ApacheServiceControl;
use Tuleap\System\ServiceControl;
use WrapperLogger;
use Exception;

class SvnrootUpdater
{
    const QUEUE_PREFIX = 'tuleap_svnroot_update';
    const TOPIC        = 'tuleap.svn.svnroot.update';

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
        $this->logger = new WrapperLogger($logger, 'svnroot_updater');
        $this->queue  = QueueFactory::getPersistentQueue($this->logger, self::QUEUE_PREFIX);
    }

    public function push()
    {
        $this->logger->info('Send message to '.self::TOPIC);
        $this->queue->pushSinglePersistentMessage(self::TOPIC, 'Update');
        $this->logger->debug('Done');
    }

    /**
     * @param string $server_id
     */
    public function listen($server_id)
    {
        $this->logger->info("Wait for messages");

        $generate = function () {
            ForgeConfig::set('svn_root_file', '/etc/httpd/conf.d/svnroot.conf');

            $apache_conf_generator = new ApacheConfGenerator(
                (new ApacheServiceControl(
                    new ServiceControl()
                ))->disableInitUsage(),
                Backend::instance('SVN')
            );
            $apache_conf_generator->generate();
        };

        $generate();

        $logger = $this->logger;

        $this->queue->listen(self::QUEUE_PREFIX.$server_id, self::TOPIC, function ($msg) use ($logger, $generate) {
            try {
                $logger->info("Received ", $msg->body);
                $generate();
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                $logger->info("Update completed");
            } catch (Exception $e) {
                $logger->error("Caught exception ".get_class($e).": ".$e->getMessage());
            }
        });
    }
}
