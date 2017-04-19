#!/usr/share/tuleap/src/utils/php-launcher.sh
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

require_once 'pre.php';

$locker = new Tuleap\System\DaemonLocker('/var/run/svnroot_updater.pid');

$logger = new TruncateLevelLogger(
    new BackendLogger('/var/log/tuleap/svnroot_updater.log'),
    ForgeConfig::get('sys_logger_level')
);

try {
    $locker->isRunning();

    $logger->info("Start service");

    $updater = new  Tuleap\Svn\SvnrootUpdater($logger);
    $updater->listen('backend-svn-1');
} catch (Exception $exception) {
    fwrite(STDERR, '*** ERROR: '.$exception->getMessage()."\n");
    exit(1);
}
