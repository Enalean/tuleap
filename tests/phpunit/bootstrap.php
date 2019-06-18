<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

$basedir      = dirname(__DIR__, 2);
$src_path     = $basedir.'/src';
$include_path = $basedir.'/src/www/include';

ini_set('include_path', ini_get('include_path').':'.$src_path.':'.$include_path);

require_once __DIR__ . '/../../src/vendor/autoload.php';
require_once __DIR__ . '/../../src/www/themes/FlamingParrot/FlamingParrot_Theme.class.php';
require_once __DIR__ . '/../../src/www/themes/BurningParrot/BurningParrotTheme.php';
require_once __DIR__ . '/../lib/Network/HTTPResponseFunctionsOverload.php';

foreach (glob(__DIR__ . '/../../plugins/*/phpunit/bootstrap.php') as $bootstrap_plugin) {
    require_once $bootstrap_plugin;
}

require_once __DIR__ . '/../../src/vendor/squizlabs/php_codesniffer/autoload.php';
