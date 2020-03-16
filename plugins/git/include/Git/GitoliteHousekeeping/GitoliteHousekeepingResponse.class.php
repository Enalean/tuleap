<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Git_GitoliteHousekeeping_GitoliteHousekeepingResponse
{

    public const ANSI_NOCOLOR = "\033[0m";
    public const ANSI_GREEN   = "\033[32m";
    public const ANSI_YELLOW  = "\033[35m";
    public const ANSI_RED     = "\033[31m";

    public const LOG_PREFIX = '[GITOLITE_HOUSEKEEPING] ';

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function abort()
    {
        $this->error('Aborting');
        exit(1);
    }

    public function success()
    {
        $this->info('Exiting with success status');
        exit(0);
    }

    public function error($msg)
    {
        $this->logger->error(self::LOG_PREFIX . $msg);
        echo self::ANSI_RED . '[ERROR] ' . self::ANSI_NOCOLOR . $msg . PHP_EOL;
    }

    public function info($msg)
    {
        $this->logger->info(self::LOG_PREFIX . $msg);
        echo self::ANSI_GREEN . '[INFO] ' . self::ANSI_NOCOLOR . $msg . PHP_EOL;
    }
}
