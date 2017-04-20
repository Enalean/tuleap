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
 */

require_once 'pre.php';


if (! isset($argv[1])) {
    fwrite(STDERR, "*** ERROR missing log file as argument\n");
    exit(1);
}

$logger = new TruncateLevelLogger(
    new BackendLogger('/var/log/tuleap/syslog'),
    ForgeConfig::get('sys_logger_level')
);

$queue = new \Tuleap\Httpd\PostRotateEvent($logger);

$queue->push($argv[1]);

exit(0);
