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

class HookConfigUpdator
{
    /**
     * @var HookDao
     */
    private $hook_dao;
    /**
     * @var \ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var HookConfigChecker
     */
    private $hook_config_checker;
    /**
     * @var HookConfigSanitizer
     */
    private $hook_config_sanitizer;
    /**
     * @var ProjectHistoryFormatter
     */
    private $project_history_formatter;

    public function __construct(
        HookDao $hook_dao,
        \ProjectHistoryDao $project_history_dao,
        HookConfigChecker $hook_config_checker,
        HookConfigSanitizer $hook_config_sanitizer,
        ProjectHistoryFormatter $project_history_formatter,
    ) {
        $this->hook_dao                  = $hook_dao;
        $this->project_history_dao       = $project_history_dao;
        $this->hook_config_checker       = $hook_config_checker;
        $this->hook_config_sanitizer     = $hook_config_sanitizer;
        $this->project_history_formatter = $project_history_formatter;
    }

    public function updateHookConfig(Repository $repository, array $hook_config)
    {
        if (! $this->hook_config_checker->hasConfigurationChanged($repository, $hook_config)) {
            return;
        }

        $this->update($repository, $hook_config);

        $this->project_history_dao->groupAddHistory(
            'svn_multi_repository_hook_update',
            "Repository: " . $repository->getName() . PHP_EOL . $this->project_history_formatter->getHookConfigHistory($hook_config),
            $repository->getProject()->getID()
        );
    }

    public function initHookConfiguration(Repository $repository, array $hook_config)
    {
        $this->update($repository, $hook_config);
    }

    private function update(Repository $repository, array $hook_config)
    {
        $this->hook_dao->updateHookConfig(
            $repository->getId(),
            $this->hook_config_sanitizer->sanitizeHookConfigArray($hook_config)
        );
    }
}
