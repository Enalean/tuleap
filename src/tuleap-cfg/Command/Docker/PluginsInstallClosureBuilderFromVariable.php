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

use Tuleap\Option\Option;
use TuleapCfg\Command\ProcessFactory;

final class PluginsInstallClosureBuilderFromVariable implements PluginsInstallClosureBuilder
{
    private const VARIABLE_NAME = 'TLP_INSTALL_PLUGINS_TO_ENABLE';

    public function __construct(
        private readonly VariableProviderInterface $variable_provider,
        private readonly ProcessFactory $process_factory,
    ) {
    }

    /**
     * @psalm-return Option<Closure():void>
     */
    public function buildClosureToInstallPlugins(): Option
    {
        $plugins_list = trim($this->variable_provider->get(self::VARIABLE_NAME));
        if ($plugins_list === '') {
            /** @psalm-var \Psl\Type\TypeInterface<Closure():void> $type */
            $type = '';
            return Option::nothing($type);
        }

        return Option::fromValue(
            function () use ($plugins_list): void {
                $this->process_factory->getProcessWithoutTimeout(['sudo', '-u', 'codendiadm', '/usr/bin/tuleap', 'plugin:install', '--', $plugins_list])->mustRun();
            }
        );
    }
}
