<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Queue;

use Tuleap\Config\InvalidConfigKeyValueException;
use Tuleap\Config\ValueValidator;
use Tuleap\Plugin\MandatoryAsyncWorkerSetupPluginInstallRequirement;
use Tuleap\Plugin\RetrieveEnabledPlugins;

final class NbBackendWorkersConfigValidator implements ValueValidator
{
    private function __construct(private readonly RetrieveEnabledPlugins $retrieve_enabled_plugins)
    {
    }

    public static function buildSelf(): ValueValidator
    {
        return self::buildWithPluginRetriever(\PluginManager::instance());
    }

    public static function buildWithPluginRetriever(RetrieveEnabledPlugins $retriever): self
    {
        return new self($retriever);
    }

    public function checkIsValid(string $value): void
    {
        if ((int) $value < 0) {
            throw new InvalidConfigKeyValueException("Nb backend workers cannot be a negative number");
        }

        if ((int) $value > 0) {
            return;
        }

        $plugins_that_need_workers = [];
        foreach ($this->retrieve_enabled_plugins->getEnabledPlugins() as $plugin) {
            foreach ($plugin->getInstallRequirements() as $install_requirement) {
                if ($install_requirement instanceof MandatoryAsyncWorkerSetupPluginInstallRequirement) {
                    $plugins_that_need_workers[] = $plugin->getName();
                }
            }
        }

        if (count($plugins_that_need_workers) > 0) {
            throw new InvalidConfigKeyValueException("Nb backend workers cannot be 0, the following plugins need workers: " . implode(', ', $plugins_that_need_workers));
        }
    }
}
