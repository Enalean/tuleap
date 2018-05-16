<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

use Logger;
use UserManager;
use WrapperLogger;

class DBWriter
{
    const YESTERDAY_LOG_FILE = '/var/log/httpd/svn_log.1';

    private $db_writer_plugin;
    private $db_writer_core;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger, DBWriterPlugin $db_writer_plugin, DBWriterCore $db_writer_core)
    {
        $this->logger           = new WrapperLogger($logger, 'svn.log');
        $this->db_writer_plugin = $db_writer_plugin;
        $this->db_writer_core   = $db_writer_core;
    }

    public static function build(Logger $logger)
    {
        $user_cache = new DBWriterUserCache(UserManager::instance());
        return new DBWriter(
            $logger,
            new DBWriterPlugin(
                new DBWriterPluginDao(),
                $user_cache
            ),
            new DBWriterCore(
                new DBWriterCoreDao(),
                $user_cache
            )
        );
    }

    public function postrotate()
    {
        if (is_file(self::YESTERDAY_LOG_FILE)) {
            $this->logger->info('Parse '.self::YESTERDAY_LOG_FILE);
            $this->saveFromFile(self::YESTERDAY_LOG_FILE);
            $this->logger->info('Parse '.self::YESTERDAY_LOG_FILE.' completed');
        }
    }

    private function saveFromFile($filename)
    {
        $parser = new Parser();
        $this->save($parser->parse($filename));
    }

    private function save(LogCache $log_cache)
    {
        $this->db_writer_plugin->save($log_cache);
        $this->db_writer_core->save($log_cache);
    }
}
