<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

require_once __DIR__ . '/../../../../src/www/include/pre.php';
require_once __DIR__ . '/../../../../tests/rest/vendor/autoload.php';
require_once __DIR__ . '/../../include/docmanPlugin.php';

use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\DocmanForbidWritersDataBuilder;
use Tuleap\Docman\Test\rest\DocmanWithMetadataActivatedDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanProjectBuilder;

$plugin_manager = PluginManager::instance();
$plugin_manager->installAndEnable('docman');

$data_builder = new DocmanDataBuilder(
    new DocmanProjectBuilder(DocmanDataBuilder::PROJECT_NAME)
);
$data_builder->setUp();

$data_builder_metadata = new DocmanWithMetadataActivatedDataBuilder(
    new DocmanProjectBuilder(DocmanWithMetadataActivatedDataBuilder::PROJECT_NAME)
);
$data_builder_metadata->setUp();

$data_builder_forbid = new DocmanForbidWritersDataBuilder(
    new DocmanProjectBuilder(DocmanForbidWritersDataBuilder::PROJECT_NAME),
    \UserManager::instance(),
    new \Tuleap\Docman\Settings\SettingsDAO(),
    \ProjectManager::instance(),
);
$data_builder_forbid->setUp();
