<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\AgileDashboard\BlockScrumAccess;
use Tuleap\AgileDashboard\Milestone\Sidebar\DuplicateMilestonesInSidebarConfig;
use Tuleap\AgileDashboard\Milestone\Sidebar\UpdateMilestonesInSidebarConfig;

class AgileDashboard_ConfigurationManager
{
    public const DEFAULT_SCRUM_TITLE = 'Scrum';

    public function __construct(
        private readonly AgileDashboard_ConfigurationDao $dao,
        private readonly \Tuleap\Kanban\Legacy\LegacyKanbanRetriever $kanban_configuration_dao,
        private readonly Psr\EventDispatcher\EventDispatcherInterface $event_dispatcher,
        private readonly DuplicateMilestonesInSidebarConfig $milestones_in_sidebar_config_duplicator,
        private readonly UpdateMilestonesInSidebarConfig $milestones_in_sidebar_config,
    ) {
    }

    public function kanbanIsActivatedForProject(int $project_id): bool
    {
        return $this->kanban_configuration_dao->isKanbanActivated($project_id);
    }

    public function scrumIsActivatedForProject(Project $project): bool
    {
        $block_scrum_access = new BlockScrumAccess($project);
        $this->event_dispatcher->dispatch($block_scrum_access);
        if (! $block_scrum_access->isScrumAccessEnabled()) {
            return false;
        }
        $row = $this->dao->isScrumActivated($project->getID())->getRow();
        if ($row) {
            return $row['scrum'];
        }

        return true;
    }

    public function getScrumTitle($project_id)
    {
        $row = $this->dao->getScrumTitle($project_id);

        if ($row) {
            return $row['scrum_title'];
        }

        return self::DEFAULT_SCRUM_TITLE;
    }

    public function updateConfiguration(
        $project_id,
        $scrum_is_activated,
        bool $should_sidebar_display_last_milestones,
    ): void {
        $this->dao->updateConfiguration(
            $project_id,
            $scrum_is_activated,
        );

        $should_sidebar_display_last_milestones
            ? $this->milestones_in_sidebar_config->activateMilestonesInSidebar((int) $project_id)
            : $this->milestones_in_sidebar_config->deactivateMilestonesInSidebar((int) $project_id);
    }

    public function duplicate($project_id, $template_id): void
    {
        $this->dao->duplicate($project_id, $template_id);
        $this->milestones_in_sidebar_config_duplicator->duplicate($project_id, $template_id);
    }
}
