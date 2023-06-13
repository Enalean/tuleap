<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace TuleapCfg\Command\Docker;

use Tuleap\Test\PHPUnit\TestCase;
use TuleapCfg\Command\ProcessFactory;

final class PluginsInstallClosureBuilderFromVariableTest extends TestCase
{
    public function testDoNothingWhenVariableIsEmpty(): void
    {
        $variable_provider = new class implements VariableProviderInterface {
            public function get(string $key): string
            {
                return '';
            }
        };
        $builder           = new PluginsInstallClosureBuilderFromVariable($variable_provider, new ProcessFactory());

        $result = $builder->buildClosureToInstallPlugins();

        self::assertTrue($result->isNothing());
    }

    public function testProvideCallableInstallingPlugins(): void
    {
        $variable_provider = new class implements VariableProviderInterface {
            public function get(string $key): string
            {
                return 'plugin_a plugin_b';
            }
        };

        $builder = new PluginsInstallClosureBuilderFromVariable($variable_provider, new ProcessFactory());

        $result = $builder->buildClosureToInstallPlugins();

        self::assertTrue($result->isValue());
    }
}
