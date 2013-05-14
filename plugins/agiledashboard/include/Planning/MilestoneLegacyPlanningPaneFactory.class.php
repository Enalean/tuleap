<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * I build the legacy "Backlog/Planning" pane for a Planning_Milestone
 */
class Planning_MilestoneLegacyPlanningPaneFactory {

    /** @var Codendi_Request */
    private $request;

    /** @var string */
    private $theme_path;

    /** @var Planning_ViewBuilder */
    private $view_builder;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var Tracker_HierarchyFactory */
    private $hierarchy_factory;

    /** @var Planning_MilestoneRedirectParameter */
    private $redirect_parameter;

    public function __construct(
        Codendi_Request $request,
        Planning_MilestoneFactory $milestone_factory,
        Tracker_HierarchyFactory  $hierarchy_factory,
        Planning_ViewBuilder $view_builder,
        $theme_path,
        Planning_MilestoneRedirectParameter $redirect_parameter
    ) {
        $this->request            = $request;
        $this->theme_path         = $theme_path;
        $this->view_builder       = $view_builder;
        $this->milestone_factory  = $milestone_factory;
        $this->hierarchy_factory  = $hierarchy_factory;
        $this->redirect_parameter = $redirect_parameter;
    }

    /** @return AgileDashboard_MilestonePlanningPaneInfo */
    public function getPaneInfo(Planning_Milestone $milestone) {
        return new AgileDashboard_MilestonePlanningPaneInfo($milestone, $this->theme_path);
    }

    /** @return AgileDashboard_MilestonePlanningPane */
    public function getPane(Planning_Milestone $milestone, AgileDashboard_MilestonePlanningPaneInfo $info) {
        // Should we make public the milestone of the $info?
        // That way we could do:
        // $milestone = $info->getMilestone();

        $planning     = $milestone->getPlanning();
        $content_view = $this->buildContentView($this->view_builder, $planning, $milestone);

        $milestone_plan = $this->milestone_factory->getMilestonePlan($this->getCurrentUser(), $milestone);

        $milestone_planning_presenter = new AgileDashboard_MilestonePlanningPresenter(
            $content_view,
            $milestone_plan,
            $this->getCurrentUser(),
            $this->redirect_parameter->getPlanningRedirectToSelf($milestone)
        );
        return new AgileDashboard_MilestonePlanningPane($info, $milestone_planning_presenter);
    }

    protected function buildContentView(
        Planning_ViewBuilder $view_builder,
        Planning             $planning,
        Planning_Milestone   $milestone
    ) {

        $already_planned_artifact_ids = $this->getAlreadyPlannedArtifactsIds($milestone);
        $cross_search_query           = $this->getCrossSearchQuery($milestone);
        $planning_redirect_to_self    = $this->redirect_parameter->getPlanningRedirectToSelf($milestone);
        $backlog_tracker_ids          = $this->hierarchy_factory->getHierarchy(array($planning->getBacklogTrackerId()))->flatten();
        $backlog_actions_presenter    = new Planning_BacklogActionsPresenter( $planning->getBacklogTracker(), $milestone, $planning_redirect_to_self);

        $view = $view_builder->build(
            $this->getCurrentUser(),
            $milestone->getProject(),
            $cross_search_query,
            $already_planned_artifact_ids,
            $backlog_tracker_ids,
            $planning,
            $backlog_actions_presenter,
            $planning_redirect_to_self
        );

        return $view;
    }

    private function getAlreadyPlannedArtifactsIds(Planning_Milestone $milestone) {
        return array_map(array($this, 'getArtifactId'), $this->getAlreadyPlannedArtifacts($milestone));
    }

    /**
     * @param array of Planning_Milestone $available_milestones
     *
     * @return array of Tracker_Artifact
     */
    private function getAlreadyPlannedArtifacts(Planning_Milestone $milestone) {
        $linked_items = array();
        foreach ($this->getAllMilestonesOfCurrentPlanning($milestone) as $other_milestone) {
            $linked_items = array_merge($linked_items, $other_milestone->getLinkedArtifacts($this->getCurrentUser()));
        }
        return $linked_items;
    }

    private function getArtifactId(Tracker_Artifact $artifact) {
        return $artifact->getId();
    }

    private function getCurrentUser() {
        return $this->request->getCurrentUser();
    }

    private function getAllMilestonesOfCurrentPlanning(Planning_Milestone $milestone) {
        return $this->milestone_factory->getAllMilestones($this->getCurrentUser(), $milestone->getPlanning());
    }

    private function getCrossSearchQuery(Planning_Milestone $milestone) {
        $request_criteria      = $this->getArrayFromRequest('criteria');
        $semantic_criteria     = $this->getArrayFromRequest('semantic_criteria');
        $artifact_criteria     = $this->getArtifactCriteria($milestone);

        return new Tracker_CrossSearch_Query($request_criteria, $semantic_criteria, $artifact_criteria);
    }

    private function getArrayFromRequest($parameter_name) {
        $request_criteria = array();
        $valid_criteria   = new Valid_Array($parameter_name);
        $valid_criteria->required();
        if ($this->request->valid($valid_criteria)) {
            $request_criteria = $this->request->get($parameter_name);
        }
        return $request_criteria;
    }

    private function getArtifactCriteria(Planning_Milestone $milestone) {
        $criteria = $this->getArrayFromRequest('artifact_criteria');
        if(empty($criteria) && $milestone->getArtifact()) {
            $criteria = $this->getPreselectedCriteriaFromAncestors($milestone);
        }
        return $criteria;
    }

    private function getPreselectedCriteriaFromAncestors(Planning_Milestone $milestone) {
        $preselected_criteria = array();
        foreach($milestone->getAncestors() as $ancestor_milestone) {
            $preselected_criteria[$ancestor_milestone->getArtifact()->getTrackerId()] = array($ancestor_milestone->getArtifactId());
        }
        return $preselected_criteria;
    }
}
?>
