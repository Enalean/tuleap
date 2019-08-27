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

namespace Tuleap\Taskboard\Routing;

use Cardwall_OnTop_Dao;
use PFUser;
use Planning_MilestoneFactory;
use PluginManager;
use taskboardPlugin;
use Tuleap\Request\NotFoundException;

class MilestoneExtractor
{

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;
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

    public function __construct(
        Planning_MilestoneFactory $milestone_factory,
        Cardwall_OnTop_Dao $cardwall_on_top_dao,
        PluginManager $plugin_manager,
        taskboardPlugin $taskboard_plugin
    ) {
        $this->milestone_factory = $milestone_factory;
        $this->cardwall_on_top_dao = $cardwall_on_top_dao;
        $this->plugin_manager = $plugin_manager;
        $this->taskboard_plugin = $taskboard_plugin;
    }

    /**
     * @throws NotFoundException
     */
    public function getMilestone(PFUser $user, array $variables): \Planning_Milestone
    {
        $milestone = $this->milestone_factory->getBareMilestoneByArtifactId($user, (int) $variables['id']);
        if (! $milestone) {
            throw new NotFoundException(dgettext('tuleap-taskboard', "Milestone not found."));
        }

        if ((string) $milestone->getProject()->getUnixNameMixedCase() !== (string) $variables['project_name']) {
            throw new NotFoundException(dgettext('tuleap-taskboard', "Milestone not found."));
        }

        if (! $this->plugin_manager->isPluginAllowedForProject(
            $this->taskboard_plugin,
            $milestone->getProject()->getID()
        )) {
            throw new NotFoundException(
                sprintf(
                    dgettext('tuleap-taskboard', "Taskboard is not activated in project %s."),
                    $milestone->getProject()->getUnconvertedPublicName()
                )
            );
        }

        if (! $this->cardwall_on_top_dao->isEnabled($milestone->getTrackerId())) {
            throw new NotFoundException(dgettext('tuleap-taskboard', "Taskboard not found."));
        }

        return $milestone;
    }
}
