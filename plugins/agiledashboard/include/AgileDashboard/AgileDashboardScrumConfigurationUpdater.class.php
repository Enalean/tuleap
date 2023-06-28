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

use Tuleap\AgileDashboard\ExplicitBacklog\ConfigurationUpdater;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDisabler;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneEnabler;
use Tuleap\AgileDashboard\Planning\PlanningAdministrationDelegation;

class AgileDashboardScrumConfigurationUpdater
{
    /** @var int */
    private $project_id;

    /** @var Codendi_Request */
    private $request;

    /** @var AgileDashboard_ConfigurationManager */
    private $config_manager;

    /** @var AgileDashboardConfigurationResponse */
    private $response;

    /** @var AgileDashboard_FirstScrumCreator */
    private $first_scrum_creator;
    /**
     * @var ScrumForMonoMilestoneEnabler
     */
    private $scrum_mono_milestone_enabler;
    /**
     * @var ScrumForMonoMilestoneDisabler
     */
    private $scrum_mono_milestone_disabler;
    /**
     * @var ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;

    /**
     * @var ConfigurationUpdater
     */
    private $configuration_updater;
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(
        Codendi_Request $request,
        AgileDashboard_ConfigurationManager $config_manager,
        AgileDashboardConfigurationResponse $response,
        AgileDashboard_FirstScrumCreator $first_scrum_creator,
        ScrumForMonoMilestoneEnabler $scrum_mono_milestone_enabler,
        ScrumForMonoMilestoneDisabler $scrum_mono_milestone_disabler,
        ScrumForMonoMilestoneChecker $scrum_mono_milestone_checker,
        ConfigurationUpdater $configuration_updater,
        \Psr\EventDispatcher\EventDispatcherInterface $event_dispatcher,
    ) {
        $this->request                       = $request;
        $this->project_id                    = (int) $this->request->get('group_id');
        $this->config_manager                = $config_manager;
        $this->response                      = $response;
        $this->first_scrum_creator           = $first_scrum_creator;
        $this->scrum_mono_milestone_enabler  = $scrum_mono_milestone_enabler;
        $this->scrum_mono_milestone_disabler = $scrum_mono_milestone_disabler;
        $this->scrum_mono_milestone_checker  = $scrum_mono_milestone_checker;
        $this->configuration_updater         = $configuration_updater;
        $this->event_dispatcher              = $event_dispatcher;
    }

    public function updateConfiguration(): void
    {
        $block_scrum_access = new \Tuleap\AgileDashboard\BlockScrumAccess($this->request->getProject());
        $this->event_dispatcher->dispatch($block_scrum_access);
        if (! $block_scrum_access->isScrumAccessEnabled()) {
            return;
        }

        if (! $this->request->exist('scrum-title-admin')) {
            $this->response->missingScrumTitle();

            return;
        }

        $scrum_is_activated = $this->getActivatedScrum();

        $this->config_manager->updateConfiguration(
            $this->project_id,
            $scrum_is_activated,
            $this->config_manager->kanbanIsActivatedForProject($this->project_id),
            $this->getScrumTitle(),
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

    private function getActivatedScrum()
    {
        $scrum_was_activated = $this->config_manager->scrumIsActivatedForProject($this->request->getProject());
        $scrum_is_activated  = $this->request->get('activate-scrum');

        if ($scrum_is_activated && ! $scrum_was_activated) {
            $this->response->scrumActivated();
        }

        return $scrum_is_activated;
    }

    private function getScrumTitle()
    {
        $old_scrum_title = $this->config_manager->getScrumTitle($this->project_id);
        $scrum_title     = trim($this->request->get('scrum-title-admin'));

        if ($scrum_title !== $old_scrum_title) {
            $this->response->scrumTitleChanged();
        }

        if ($scrum_title == '') {
            $this->response->emptyScrumTitle();
            $scrum_title = $old_scrum_title;
        }

        return $scrum_title;
    }
}
