#!/usr/share/tuleap/src/utils/php-launcher.sh
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
 */

use Tuleap\System\ApacheServiceControl;
use Tuleap\System\ServiceControl;
use TuleapCfg\Command\ProcessFactory;

require_once __DIR__ . '/../../www/include/pre.php';

$logger = new WrapperLogger(
    BackendLogger::getDefaultLogger(),
    'httpd.postrotate'
);

$logger->info("Restart apache");
(new ApacheServiceControl(new ServiceControl(), new ProcessFactory()))->reload();
$logger->info("Restart apache completed");

$event_manager->processEvent(new \Tuleap\Httpd\PostRotateEvent($logger));
