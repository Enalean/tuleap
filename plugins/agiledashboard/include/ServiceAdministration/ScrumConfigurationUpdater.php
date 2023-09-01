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
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDisabler;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneEnabler;
use Tuleap\AgileDashboard\Planning\PlanningAdministrationDelegation;

class ScrumConfigurationUpdater
{
    private readonly int $project_id;

    public function __construct(
        private readonly \Codendi_Request $request,
        private readonly \AgileDashboard_ConfigurationManager $config_manager,
        private readonly ConfigurationResponse $response,
        private readonly \AgileDashboard_FirstScrumCreator $first_scrum_creator,
        private readonly ScrumForMonoMilestoneEnabler $scrum_mono_milestone_enabler,
        private readonly ScrumForMonoMilestoneDisabler $scrum_mono_milestone_disabler,
        private readonly ScrumForMonoMilestoneChecker $scrum_mono_milestone_checker,
        private readonly ConfigurationUpdater $configuration_updater,
        private readonly EventDispatcherInterface $event_dispatcher,
        private readonly \Tuleap\Kanban\SplitKanbanConfigurationChecker $split_kanban_configuration_checker,
    ) {
        $this->project_id = (int) $this->request->get('group_id');
    }

    public function updateConfiguration(): void
    {
        $block_scrum_access = new \Tuleap\AgileDashboard\BlockScrumAccess($this->request->getProject());
        $this->event_dispatcher->dispatch($block_scrum_access);
        if (! $block_scrum_access->isScrumAccessEnabled()) {
            return;
        }

        $scrum_is_activated = $this->getActivatedScrum();

        $this->config_manager->updateConfiguration(
            $this->project_id,
            $scrum_is_activated,
        );

        $this->configuration_updater->updateScrumConfiguration($this->request);

        $is_scrum_mono_milestone_enabled = $this->scrum_mono_milestone_checker->isMonoMilestoneEnabled(
            $this->project_id
        );
        if ($this->request->get('home-ease-onboarding') === false) {
            if ($this->request->get('activate-scrum-v2') && $is_scrum_mono_milestone_enabled === false) {
                $this->scrum_mono_milestone_enabler->enableScrumForMonoMilestones($this->project_id);
            } elseif ($this->request->get('activate-scrum-v2') == false && $is_scrum_mono_milestone_enabled === true) {
                $this->scrum_mono_milestone_disabler->disableScrumForMonoMilestones($this->project_id);
            }
        }

        if ($scrum_is_activated) {
            $planning_administration_delegation = new PlanningAdministrationDelegation($this->request->getProject());
            $this->event_dispatcher->dispatch($planning_administration_delegation);

            if (
                $this->request->get('activate-scrum-v2') == false && $is_scrum_mono_milestone_enabled === false &&
                ! $planning_administration_delegation->isPlanningAdministrationDelegated()
            ) {
                $this->first_scrum_creator->createFirstScrum();
            }
        }

        $GLOBALS['Response']->addFeedback(
            \Feedback::INFO,
            dgettext(
                'tuleap-agiledashboard',
                'Scrum configuration successfully updated.'
            )
        );

        $this->response->scrumConfigurationUpdated();
    }

    private function getActivatedScrum(): bool|string
    {
        $project = $this->request->getProject();

        $scrum_was_activated = $this->config_manager->scrumIsActivatedForProject($project);
        if ($this->split_kanban_configuration_checker->isProjectAllowedToUseSplitKanban($project)) {
            return $scrum_was_activated;
        }

        $scrum_is_activated = $this->request->get('activate-scrum');

        if ($scrum_is_activated && ! $scrum_was_activated) {
            $this->response->scrumActivated();
        }

        return $scrum_is_activated;
    }
}
