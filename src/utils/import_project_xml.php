<?php
// #!/usr/share/codendi/src/utils/php-launcher.sh

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

require_once 'pre.php';
require_once 'common/project/ProjectXMLImporter.class.php';

if ($argc < 4) {
    echo <<< EOT
Usage: $argv[0] project_id admin_user_name xml_file_path
Create a project trackers, agiledashboard and cardwall from XML format

EOT;
    exit(1);
}

$user_manager = UserManager::instance();
$xml_importer = new ProjectXMLImporter(
    EventManager::instance(),
    ProjectManager::instance()
);

try {
    $user = $user_manager->forceLogin($argv[2]);
    $xml_importer->import($argv[1], $argv[3]);

    if ((! $user->isSuperUser() && ! $user->isAdmin($project_id)) || ! $user->isActive()) {
        throw new RuntimeException($GLOBALS['Language']->getText('project_import', 'invalid_user', array($user_name)));
    }
} catch (Exception $exception) {
    echo "*** ERROR: ".$exception->getMessage().PHP_EOL;
    exit(1);
}

?>
