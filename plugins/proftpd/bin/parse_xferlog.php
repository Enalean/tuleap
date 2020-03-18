<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/proftpdPlugin.php';

$plugin_manager = PluginManager::instance();
$plugin = $plugin_manager->getPluginByName('proftpd');
if ($plugin && $plugin_manager->isPluginAvailable($plugin)) {
    $file_importer = new Tuleap\ProFTPd\Xferlog\FileImporter(
        new Tuleap\ProFTPd\Xferlog\Dao(),
        new Tuleap\ProFTPd\Xferlog\Parser(),
        UserManager::instance(),
        ProjectManager::instance(),
        new UserDao(),
        $plugin->getPluginInfo()->getPropVal('proftpd_base_directory')
    );

    $file_importer->import($argv[1]);

    echo "{$file_importer->getNbImportedLines()} lines imported" . PHP_EOL;
    $errors    = $file_importer->getErrors();
    $nb_errors = count($errors);
    if ($nb_errors) {
        $logger = BackendLogger::getDefaultLogger();
        echo "$nb_errors errors" . PHP_EOL;
        foreach ($errors as $error) {
            $logger->error('[Proftpd][xferlog parse] ' . $error);
            echo "*** ERROR: " . $error . PHP_EOL;
        }
    }
} else {
    echo "*** ERROR: proftpd plugin not available" . PHP_EOL;
}
