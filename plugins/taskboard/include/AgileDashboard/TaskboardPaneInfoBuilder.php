<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\AgileDashboard;

use Cardwall_OnTop_Dao;
use Planning_Milestone;
use PluginManager;
use taskboardPlugin;

class TaskboardPaneInfoBuilder
{
    /**
     * @var PluginManager
     */
    private $plugin_manager;
    /**
     * @var taskboardPlugin
     */
    private $taskboard_plugin;
    /**
     * @var Cardwall_OnTop_Dao
     */
    private $dao;

    public function __construct(
        PluginManager $plugin_manager,
        taskboardPlugin $taskboard_plugin,
        Cardwall_OnTop_Dao $dao
    ) {
        $this->plugin_manager   = $plugin_manager;
        $this->taskboard_plugin = $taskboard_plugin;
        $this->dao              = $dao;
    }

    public function getPaneForMilestone(Planning_Milestone $milestone): ?TaskboardPaneInfo
    {
        if (! $this->plugin_manager->isPluginAllowedForProject(
            $this->taskboard_plugin,
            $milestone->getProject()->getID()
        )) {
            return null;
        }

        if (! $this->dao->isEnabled($milestone->getTrackerId())) {
            return null;
        }

        return new TaskboardPaneInfo($milestone);
    }
}
