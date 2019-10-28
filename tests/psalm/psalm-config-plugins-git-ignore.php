#!/usr/bin/env php
<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

use Tuleap\Test\Psalm\PsalmCommandLauncherWithIgnoreDirectory;
use Tuleap\Test\Psalm\PsalmIgnoreGitExcludedTuleapPlugins;
use Tuleap\Test\Psalm\ShellPassthroughUsingPassthruFunction;

require_once __DIR__ . '/../../src/vendor/autoload.php';

$psalm_command_launcher = new PsalmCommandLauncherWithIgnoreDirectory(
    sys_get_temp_dir(),
    new PsalmIgnoreGitExcludedTuleapPlugins(new System_Command()),
    new ShellPassthroughUsingPassthruFunction()
);

$exit_code = $psalm_command_launcher->execute($_SERVER['_'], ...$argv);

exit($exit_code);
