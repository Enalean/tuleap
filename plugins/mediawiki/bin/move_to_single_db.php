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

require_once __DIR__ . '/../../../src/www/include/pre.php';

if ($argc != 2) {
    die("Usage: move_to_single_db.php [project_id|all]\n");
}

if (is_numeric($argv[1]) || $argv[1] == Tuleap\Mediawiki\Events\SystemEvent_MEDIAWIKI_TO_CENTRAL_DB::ALL) {
    $system_event_manager = SystemEventManager::instance();
    $system_event_manager->createEvent(
        Tuleap\Mediawiki\Events\SystemEvent_MEDIAWIKI_TO_CENTRAL_DB::NAME,
        $argv[1],
        SystemEvent::PRIORITY_HIGH,
        SystemEvent::OWNER_ROOT,
        'Tuleap\Mediawiki\Events\SystemEvent_MEDIAWIKI_TO_CENTRAL_DB'
    );
    exit(0);
}

die("Invalid argument");
