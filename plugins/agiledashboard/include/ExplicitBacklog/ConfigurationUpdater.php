<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use Codendi_Request;
use PFUser;
use Planning_MilestoneFactory;
use Planning_NoPlanningsException;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\AgileDashboard\BacklogItemDao;
use Tuleap\AgileDashboard\Milestone\MilestoneReportCriterionDao;
use Tuleap\AgileDashboard\Planning\PlanningAdministrationDelegation;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;
use Tuleap\DB\DBTransactionExecutor;

readonly class ConfigurationUpdater
{
    public function __construct(
        private ExplicitBacklogDao $explicit_backlog_dao,
        private MilestoneReportCriterionDao $milestone_report_criterion_dao,
        private BacklogItemDao $backlog_item_dao,
        private Planning_MilestoneFactory $milestone_factory,
        private ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        private UnplannedArtifactsAdder $unplanned_artifacts_adder,
        private AddToTopBacklogPostActionDao $add_to_top_backlog_post_action_dao,
        private DBTransactionExecutor $db_transaction_executor,
        private EventDispatcherInterface $event_dispatcher,
    ) {
    }

    public function updateScrumConfiguration(Codendi_Request $request): void
    {
        if (! $request->exist('use-explicit-top-backlog')) {
            return;
        }

        $user                     = $request->getCurrentUser();
        $project                  = $request->getProject();
        $project_id               = (int) $project->getID();
        $use_explicit_top_backlog = $this->shouldUseExplicitBacklog($request);
        if ($this->mustBeDeactivated($use_explicit_top_backlog, $project_id)) {
            $this->deactivateExplicitBacklogManagement($project_id);
        } elseif ($this->mustBeActivated($use_explicit_top_backlog, $project_id)) {
            $this->activateExplicitBacklogManagement($project, $user);
        }
    }

    private function shouldUseExplicitBacklog(Codendi_Request $request): bool
    {
        $use_explicit_backlog = (bool) $request->get('use-explicit-top-backlog');
        if ($use_explicit_backlog) {
            return true;
        }

        $planning_administration_delegation = new PlanningAdministrationDelegation($request->getProject());
        $this->event_dispatcher->dispatch($planning_administration_delegation);
        return $planning_administration_delegation->isPlanningAdministrationDelegated();
    }

    public function activateExplicitBacklogManagement(Project $project, PFUser $user): void
    {
        $this->db_transaction_executor->execute(function () use ($project, $user) {
            $project_id = (int) $project->getID();
            $this->explicit_backlog_dao->setProjectIsUsingExplicitBacklog($project_id);
            //duplicate data
            try {
                $top_milestone = $this->milestone_factory->getVirtualTopMilestone($user, $project);
            } catch (Planning_NoPlanningsException $exception) {
                return;
            }

            $backlog_items_rows = $this->backlog_item_dao->getOpenUnplannedTopBacklogArtifacts(
                $top_milestone->getPlanning()->getBacklogTrackersIds()
            );

            foreach ($backlog_items_rows as $backlog_items_row) {
                try {
                    $this->unplanned_artifacts_adder->addArtifactToTopBacklogFromIds(
                        (int) $backlog_items_row['id'],
                        $project_id
                    );
                } catch (ArtifactAlreadyPlannedException $exception) {
                    //Do nothing
                }
            }
        });
    }

    private function deactivateExplicitBacklogManagement(int $project_id): void
    {
        $project_used_add_to_top_backlog_workflow_action = $this->add_to_top_backlog_post_action_dao
            ->isAtLeastOnePostActionDefinedInProject($project_id);

        $this->db_transaction_executor->execute(function () use ($project_id) {
            $this->artifacts_in_explicit_backlog_dao->removeExplicitBacklogOfProject($project_id);
            $this->milestone_report_criterion_dao->updateAllUnplannedValueToAnyInProject($project_id);
            $this->add_to_top_backlog_post_action_dao->deleteAllPostActionsInProject($project_id);
        });

        $GLOBALS['Response']->addFeedback(
            \Feedback::INFO,
            dgettext(
                'tuleap-agiledashboard',
                'All tracker reports using "Unplanned" option for "In milestone" report criterion will now use "Any".'
            )
        );

        if ($project_used_add_to_top_backlog_workflow_action) {
            $GLOBALS['Response']->addFeedback(
                \Feedback::INFO,
                dgettext(
                    'tuleap-agiledashboard',
                    'All tracker workflow post-actions "AddToTopBacklog" have been removed.'
                )
            );
        }
    }

    private function mustBeDeactivated(bool $use_explicit_top_backlog, int $project_id): bool
    {
        return ! $use_explicit_top_backlog &&
            $this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id);
    }

    private function mustBeActivated(bool $use_explicit_top_backlog, int $project_id): bool
    {
        return $use_explicit_top_backlog &&
            ! $this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id);
    }
}
