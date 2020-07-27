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

use AgileDashboard_BacklogItemDao;
use Codendi_Request;
use MilestoneReportCriterionDao;
use PFUser;
use Planning_MilestoneFactory;
use Planning_NoPlanningsException;
use Project;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;
use Tuleap\DB\DBTransactionExecutor;

class ConfigurationUpdater
{
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var MilestoneReportCriterionDao
     */
    private $milestone_report_criterion_dao;

    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;

    /**
     * @var AgileDashboard_BacklogItemDao
     */
    private $backlog_item_dao;

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    /**
     * @var UnplannedArtifactsAdder
     */
    private $unplanned_artifacts_adder;

    /**
     * @var AddToTopBacklogPostActionDao
     */
    private $add_to_top_backlog_post_action_dao;

    public function __construct(
        ExplicitBacklogDao $explicit_backlog_dao,
        MilestoneReportCriterionDao $milestone_report_criterion_dao,
        AgileDashboard_BacklogItemDao $backlog_item_dao,
        Planning_MilestoneFactory $milestone_factory,
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        UnplannedArtifactsAdder $unplanned_artifacts_adder,
        AddToTopBacklogPostActionDao $add_to_top_backlog_post_action_dao,
        DBTransactionExecutor $db_transaction_executor
    ) {
        $this->explicit_backlog_dao               = $explicit_backlog_dao;
        $this->milestone_report_criterion_dao     = $milestone_report_criterion_dao;
        $this->db_transaction_executor            = $db_transaction_executor;
        $this->backlog_item_dao                   = $backlog_item_dao;
        $this->milestone_factory                  = $milestone_factory;
        $this->artifacts_in_explicit_backlog_dao  = $artifacts_in_explicit_backlog_dao;
        $this->unplanned_artifacts_adder          = $unplanned_artifacts_adder;
        $this->add_to_top_backlog_post_action_dao = $add_to_top_backlog_post_action_dao;
    }

    public function updateScrumConfiguration(Codendi_Request $request): void
    {
        if (! $request->exist('use-explicit-top-backlog')) {
            return;
        }

        $user                     = $request->getCurrentUser();
        $project                  = $request->getProject();
        $project_id               = (int) $project->getID();
        $use_explicit_top_backlog = (bool) $request->get('use-explicit-top-backlog');
        if ($this->mustBeDeactivated($use_explicit_top_backlog, $project_id)) {
            $this->deactivateExplicitBacklogManagement($project_id);
        } elseif ($this->mustBeActivated($use_explicit_top_backlog, $project_id)) {
            $this->activateExplicitBacklogManagement($project, $user);
        }
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
                $top_milestone->getPlanning()->getBacklogTrackersIds(),
                []
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
