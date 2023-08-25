<?php
/**
 * Copyright (c) Enalean 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Artifact;

use PFUser;
use PlanningFactory;
use PlanningPermissionsManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Planning\PlanningTrackerBacklogChecker;
use Tuleap\Kanban\SplitKanbanConfigurationChecker;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonAction;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonLinkPresenter;
use Tuleap\Tracker\Artifact\Artifact;

class AdditionalArtifactActionBuilder
{
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var PlanningPermissionsManager
     */
    private $planning_permissions_manager;

    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    /**
     * @var PlannedArtifactDao
     */
    private $planned_artifact_dao;

    /**
     * @var JavascriptAssetGeneric
     */
    private $include_assets;

    /**
     * @var PlanningTrackerBacklogChecker
     */
    private $planning_tracker_backlog_checker;
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(
        ExplicitBacklogDao $explicit_backlog_dao,
        PlanningFactory $planning_factory,
        PlanningPermissionsManager $planning_permissions_manager,
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        PlannedArtifactDao $planned_artifact_dao,
        JavascriptAssetGeneric $include_assets,
        PlanningTrackerBacklogChecker $planning_tracker_backlog_checker,
        EventDispatcherInterface $event_dispatcher,
        private readonly SplitKanbanConfigurationChecker $split_kanban_configuration_checker,
    ) {
        $this->explicit_backlog_dao              = $explicit_backlog_dao;
        $this->planning_factory                  = $planning_factory;
        $this->planning_permissions_manager      = $planning_permissions_manager;
        $this->artifacts_in_explicit_backlog_dao = $artifacts_in_explicit_backlog_dao;
        $this->planned_artifact_dao              = $planned_artifact_dao;
        $this->include_assets                    = $include_assets;
        $this->planning_tracker_backlog_checker  = $planning_tracker_backlog_checker;
        $this->event_dispatcher                  = $event_dispatcher;
    }

    public function buildArtifactAction(Artifact $artifact, PFUser $user): ?AdditionalButtonAction
    {
        $tracker = $artifact->getTracker();
        $project = $tracker->getProject();

        $project_id  = (int) $project->getID();
        $artifact_id = (int) $artifact->getId();

        if ($this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id) === false) {
            return null;
        }

        $block_scrum_access = new \Tuleap\AgileDashboard\BlockScrumAccess($project);
        $this->event_dispatcher->dispatch($block_scrum_access);
        if (! $block_scrum_access->isScrumAccessEnabled()) {
            return null;
        }

        $root_planning = $this->planning_factory->getRootPlanning($user, $project_id);
        if (! $root_planning) {
            return null;
        }

        if (! $this->planning_tracker_backlog_checker->isTrackerBacklogOfProjectPlanning($root_planning, $tracker)) {
            return null;
        }

        $user_has_permission = $this->planning_permissions_manager->userHasPermissionOnPlanning(
            $root_planning->getId(),
            $root_planning->getGroupId(),
            $user,
            PlanningPermissionsManager::PERM_PRIORITY_CHANGE
        );

        if (! $user_has_permission) {
            return null;
        }

        if ($this->planned_artifact_dao->isArtifactPlannedInAMilestoneOfTheProject($artifact_id, $project_id)) {
            return null;
        }
        $is_split_feature_flag_enabled = $this->split_kanban_configuration_checker->isProjectAllowedToUseSplitKanban($project);
        $link_label                    = $is_split_feature_flag_enabled ? dgettext('tuleap-agiledashboard', 'Add to backlog') : dgettext('tuleap-agiledashboard', 'Add to top backlog');
        $icon                          = 'fa-tlp-add-to-backlog';
        $id                            = 'artifact-explicit-backlog-action';
        $action                        = 'add';

        if ($this->artifacts_in_explicit_backlog_dao->isArtifactInTopBacklogOfProject($artifact_id, $project_id)) {
            $link_label =  $is_split_feature_flag_enabled ? dgettext('tuleap-agiledashboard', 'Remove from backlog') : dgettext('tuleap-agiledashboard', 'Remove from top backlog');
            $icon       = 'fa-tlp-remove-from-backlog';
            $action     = 'remove';
        }

        $link = new AdditionalButtonLinkPresenter(
            $link_label,
            '',
            "",
            $icon,
            $id,
            [
                [
                    'name'  => 'project-id',
                    'value' => $project_id,
                ],
                [
                    'name'  => 'artifact-id',
                    'value' => $artifact_id,
                ],
                [
                    'name'  => 'action',
                    'value' => $action,
                ],
            ]
        );

        return new AdditionalButtonAction(
            $link,
            $this->include_assets
        );
    }
}
