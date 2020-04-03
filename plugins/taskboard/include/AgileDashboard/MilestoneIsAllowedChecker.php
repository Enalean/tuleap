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
use PluginManager;
use taskboardPlugin;

class MilestoneIsAllowedChecker
{
    /**
     * @var Cardwall_OnTop_Dao
     */
    private $cardwall_on_top_dao;
    /**
     * @var PluginManager
     */
    private $plugin_manager;
    /**
     * @var taskboardPlugin
     */
    private $taskboard_plugin;
    /**
     * @var TaskboardUsage
     */
    private $taskboard_usage;

    public function __construct(
        Cardwall_OnTop_Dao $cardwall_on_top_dao,
        TaskboardUsage $taskboard_usage,
        PluginManager $plugin_manager,
        taskboardPlugin $taskboard_plugin
    ) {
        $this->cardwall_on_top_dao = $cardwall_on_top_dao;
        $this->plugin_manager      = $plugin_manager;
        $this->taskboard_plugin    = $taskboard_plugin;
        $this->taskboard_usage = $taskboard_usage;
    }

    public static function build(): self
    {
        $plugin_manager   = PluginManager::instance();
        $taskboard_plugin = $plugin_manager->getPluginByName('taskboard');
        if (! $taskboard_plugin instanceof \taskboardPlugin) {
            throw new \RuntimeException('Cannot instantiate taskboard plugin');
        }

        return new self(
            new Cardwall_OnTop_Dao(),
            new TaskboardUsage(new TaskboardUsageDao()),
            $plugin_manager,
            $taskboard_plugin
        );
    }

    /**
     * @throws MilestoneIsNotAllowedException
     */
    public function checkMilestoneIsAllowed(\Planning_Milestone $milestone): void
    {
        if (
            ! $this->plugin_manager->isPluginAllowedForProject(
                $this->taskboard_plugin,
                $milestone->getProject()->getID()
            )
        ) {
            throw new MilestoneIsNotAllowedException(
                sprintf(
                    dgettext('tuleap-taskboard', "Taskboard is not activated in project %s."),
                    $milestone->getProject()->getPublicName()
                )
            );
        }

        if (! $this->cardwall_on_top_dao->isEnabled($milestone->getTrackerId())) {
            throw new MilestoneIsNotAllowedException(dgettext('tuleap-taskboard', "Taskboard not found."));
        }

        if (! $this->taskboard_usage->isTaskboardAllowed($milestone->getProject())) {
            throw new MilestoneIsNotAllowedException(
                sprintf(
                    dgettext('tuleap-taskboard', "Taskboard is not activated in project %s."),
                    $milestone->getProject()->getPublicName()
                )
            );
        }
    }
}
