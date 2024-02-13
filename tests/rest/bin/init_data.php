<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use ParagonIE\EasyDB\Factory;
use Tuleap\DB\DBConfig;

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../vendor/autoload.php';

$data_builder = new REST_TestDataBuilder();
$data_builder
    ->instanciateFactories()
    ->generateUsers()
    ->delegateForgePermissions()
    ->deleteTracker()
    ->deleteProject()
    ->markProjectsAsTemplate()
    ->suspendProject()
    ->createProjectField();


// Load PHPWiki fixture
$phpwiki_fixtures = [
    [
        'path' => __DIR__ . '/../_fixtures/phpwiki/rest-test-wiki-group-list',
        'table' => 'wiki_group_list',
    ],
    [
        'path' => __DIR__ . '/../_fixtures/phpwiki/rest-test-wiki-page',
        'table' => 'wiki_page',
    ],
    [
        'path' => __DIR__ . '/../_fixtures/phpwiki/rest-test-wiki-nonempty',
        'table' => 'wiki_nonempty',
    ],
    [
        'path' => __DIR__ . '/../_fixtures/phpwiki/rest-test-wiki-version',
        'table' => 'wiki_version',
    ],
    [
        'path' => __DIR__ . '/../_fixtures/phpwiki/rest-test-wiki-recent',
        'table' => 'wiki_recent',
    ],
];

$db_with_load_infile = Factory::fromArray([
    DBConfig::getPDODSN(\ForgeConfig::get(DBConfig::CONF_DBNAME)),
    \ForgeConfig::get(DBConfig::CONF_DBUSER),
    \ForgeConfig::get(DBConfig::CONF_DBPASSWORD),
    [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
]);

foreach ($phpwiki_fixtures as $phpwiki_fixture) {
    $path  = $phpwiki_fixture['path'];
    $table = $phpwiki_fixture['table'];
    $db_with_load_infile->run("LOAD DATA LOCAL INFILE '$path' INTO TABLE $table CHARACTER SET ascii");
}

// Avoid 3rd party service call (IHaveBeenPwned) during tests
\Tuleap\DB\DBFactory::getMainTuleapDBConnection()->getDB()->run('DELETE FROM password_configuration');
