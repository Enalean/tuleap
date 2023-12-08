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
 */

namespace Tuleap\SVN\Repository;

use Tuleap\SVNCore\Repository;

class HookConfigChecker
{
    /**
     * @var HookConfigRetriever
     */
    private $hook_config_retriever;

    public function __construct(HookConfigRetriever $hook_config_retriever)
    {
        $this->hook_config_retriever = $hook_config_retriever;
    }

    public function hasConfigurationChanged(Repository $repository, array $hook_config)
    {
        $old_hook_config = $this->hook_config_retriever->getHookConfig($repository);

        return $this->hasChanged($old_hook_config, $hook_config, HookConfig::COMMIT_MESSAGE_CAN_CHANGE)
            || $this->hasChanged($old_hook_config, $hook_config, HookConfig::MANDATORY_REFERENCE);
    }

    private function hasChanged(HookConfig $old_hook_config, array $hook_config, $key)
    {
        return (bool) $old_hook_config->getHookConfig($key) !== (bool) $hook_config[$key];
    }
}
