<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ServiceAdministration;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\AgileDashboard\ExplicitBacklog\ConfigurationUpdater;
use Tuleap\AgileDashboard\Milestone\Sidebar\CheckMilestonesInSidebar;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDisabler;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneEnabler;

class ScrumConfigurationUpdater
{
    private readonly int $project_id;

    public function __construct(
        private readonly \Codendi_Request $request,
        private readonly \AgileDashboard_ConfigurationManager $config_manager,
        private readonly ScrumForMonoMilestoneEnabler $scrum_mono_milestone_enabler,
        private readonly ScrumForMonoMilestoneDisabler $scrum_mono_milestone_disabler,
        private readonly ScrumForMonoMilestoneChecker $scrum_mono_milestone_checker,
        private readonly ConfigurationUpdater $configuration_updater,
        private readonly EventDispatcherInterface $event_dispatcher,
        private readonly CheckMilestonesInSidebar $milestones_in_sidebar,
    ) {
        $this->project_id = (int) $this->request->get('group_id');
    }

    public function updateConfiguration(): void
    {
        $project = $this->request->getProject();

        $block_scrum_access = new \Tuleap\AgileDashboard\BlockScrumAccess($project);
        $this->event_dispatcher->dispatch($block_scrum_access);
        if (! $block_scrum_access->isScrumAccessEnabled()) {
            return;
        }

        $scrum_is_activated                     = $this->getActivatedScrum();
        $should_sidebar_display_last_milestones = $this->shouldSidebarDisplayLastMilestones();

        $this->config_manager->updateConfiguration(
            $this->project_id,
            $scrum_is_activated,
            $should_sidebar_display_last_milestones,
        );

        $this->configuration_updater->updateScrumConfiguration($this->request);

        $is_scrum_mono_milestone_enabled = $this->scrum_mono_milestone_checker->isMonoMilestoneEnabled(
            $this->project_id
        );

        if ($this->request->get('activate-scrum-v2') && $is_scrum_mono_milestone_enabled === false) {
            $this->scrum_mono_milestone_enabler->enableScrumForMonoMilestones($this->project_id);
        } elseif ($this->request->get('activate-scrum-v2') == false && $is_scrum_mono_milestone_enabled === true) {
            $this->scrum_mono_milestone_disabler->disableScrumForMonoMilestones($this->project_id);
        }
    }

    private function getActivatedScrum(): bool|string
    {
        $project = $this->request->getProject();

        return $this->config_manager->scrumIsActivatedForProject($project);
    }

    private function shouldSidebarDisplayLastMilestones(): bool
    {
        $project = $this->request->getProject();

        if (! $this->request->exist('should-sidebar-display-last-milestones')) {
            return $this->milestones_in_sidebar->shouldSidebarDisplayLastMilestones((int) $project->getID());
        }

        return (bool) $this->request->get('should-sidebar-display-last-milestones');
    }
}
