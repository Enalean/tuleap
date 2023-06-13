#!/opt/remi/php81/root/usr/bin/php
<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

use TuleapCfg\Command\Docker\PluginsInstallClosureBuilderFromVariable;
use TuleapCfg\Command\Docker\VariableProviderFromEnvironment;

require_once __DIR__ . '/../../../src/vendor/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

$application       = new \Symfony\Component\Console\Application();
$process_factory   = new \TuleapCfg\Command\ProcessFactory();
$variable_provider = new VariableProviderFromEnvironment();
$application->add(new \Tuleap\TEEContainer\StartContainerCommand(
    $process_factory,
    new PluginsInstallClosureBuilderFromVariable($variable_provider, $process_factory),
    $variable_provider,
));
$application->run();
