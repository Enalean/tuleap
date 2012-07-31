<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once 'common/mvc2/Controller.class.php';
require_once dirname(__FILE__).'/../BreadCrumbs/Milestone.class.php';
require_once dirname(__FILE__).'/../BreadCrumbs/NoCrumb.class.php';
require_once dirname(__FILE__).'/../BreadCrumbs/Merger.class.php';
require_once 'SearchContentView.class.php';
require_once 'MilestonePresenter.class.php';
require_once 'MilestoneFactory.class.php';

/**
 * Handles the HTTP actions related to a planning milestone.
 */
class Planning_MilestoneController extends MVC2_Controller {

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    /**
     * Store all milestones of the current planning
     *
     * @var Array of Planning_Milestone
     */
    private $all_milestones = null;

    /**
     * Instanciates a new controller.
     * 
     * TODO:
     *   - pass $request to actions (e.g. show).
     * 
     * @param Codendi_Request           $request
     * @param PlanningFactory           $planning_factory
     * @param Planning_MilestoneFactory $milestone_factory 
     */
    public function __construct(Codendi_Request           $request,
                                Planning_MilestoneFactory $milestone_factory,
                                ProjectManager            $project_manager) {
        
        parent::__construct('agiledashboard', $request);
        try {
            $this->milestone_factory = $milestone_factory;
            $project                 = $project_manager->getProject($request->get('group_id'));
            $this->milestone         = $this->milestone_factory->getMilestoneWithPlannedArtifactsAndSubMilestones(
                $this->getCurrentUser(),
                $project,
                $request->get('planning_id'),
                $request->get('aid')
            );
        } catch (Tracker_Hierarchy_MoreThanOneParentException $e) {
                $GLOBALS['Response']->addFeedback('error', $e->getMessage(), CODENDI_PURIFIER_LIGHT);
        }
    }

    public function show(Planning_ViewBuilder $view_builder) {
        $project              = $this->milestone->getProject();
        $planning             = $this->milestone->getPlanning();
        if ($this->milestone->hasAncestors()) {
            $available_milestones = $this->milestone_factory->getSiblingMilestones($this->getCurrentUser(), $this->milestone);
        } else {
            $available_milestones = $this->getAllMilestonesOfCurrentPlanning();
        }
        $content_view         = $this->buildContentView($view_builder, $planning, $project);
        $presenter            = $this->getMilestonePresenter($planning, $content_view, $available_milestones);
        
        $this->render('show', $presenter);
    }

    private function getMilestonePresenter(Planning                              $planning,
                                           Tracker_CrossSearch_SearchContentView $content_view,
                                           array                                 $available_milestones) {
        
        $planning_redirect_parameter = $this->getPlanningRedirectToSelf();
        $planning_redirect_to_new    = $this->getPlanningRedirectToNew();
        
        return new Planning_MilestonePresenter(
            $planning,
            $content_view,
            $available_milestones,
            $this->milestone,
            $this->getCurrentUser(),
            $this->request,
            $planning_redirect_parameter,
            $planning_redirect_to_new
        );
    }
    
    private function getPlanningRedirectToSelf() {
        $planning_id = (int) $this->milestone->getPlanningId();
        $artifact_id = $this->milestone->getArtifactId();
        
        return "planning[$planning_id]=$artifact_id";
    }

    private function getPlanningRedirectToNew() {
        $planning_id = (int) $this->milestone->getPlanningId();
        $artifact_id = $this->milestone->getArtifactId();

        return "planning[$planning_id]=-1";
    }

    private function getCrossSearchQuery() {
        $request_criteria      = $this->getArrayFromRequest('criteria');
        $semantic_criteria     = $this->getArrayFromRequest('semantic_criteria');
        $artifact_criteria     = $this->getArtifactCriteria();
        
        return new Tracker_CrossSearch_Query($request_criteria, $semantic_criteria, $artifact_criteria);
    }
    
    private function buildContentView(
        Planning_ViewBuilder $view_builder,
        Planning             $planning,
        Project              $project = null
    ) {
        
        $already_planned_artifact_ids = $this->getAlreadyPlannedArtifactsIds();
        $cross_search_query           = $this->getCrossSearchQuery();
        $view_builder->setHierarchyFactory(Tracker_HierarchyFactory::instance());
        
        $view = $view_builder->build(
            $this->getCurrentUser(),
            $project,
            $cross_search_query,
            $already_planned_artifact_ids,
            $planning->getBacklogTrackerId(),
            $planning,
            $this->getPlanningRedirectToSelf()
        );
        
        return $view;
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

    private function getArtifactCriteria() {
        $criteria = $this->getArrayFromRequest('artifact_criteria');
        if(empty($criteria) && $this->milestone->getArtifact()) {
            $criteria = $this->getPreselectedCriteriaFromAncestors();
        }
        return $criteria;
    }

    private function getPreselectedCriteriaFromAncestors() {
        $preselected_criteria = array();
        foreach($this->milestone->getAncestors() as $milestone) {
            $preselected_criteria[$milestone->getArtifact()->getTrackerId()] = array($milestone->getArtifactId());
        }
        return $preselected_criteria;
    }

    private function getAlreadyPlannedArtifactsIds() {
        return array_map(array($this, 'getArtifactId'), $this->getAlreadyPlannedArtifacts());
    }

    private function getArtifactId(Tracker_Artifact $artifact) {
        return $artifact->getId();
    }

    /**
     * @param array of Planning_Milestone $available_milestones
     * 
     * @return array of Tracker_Artifact
     */
    private function getAlreadyPlannedArtifacts() {
        $linked_items = array();
        foreach ($this->getAllMilestonesOfCurrentPlanning() as $milestone) {
            $linked_items = array_merge($linked_items, $milestone->getLinkedArtifacts($this->getCurrentUser()));
        }
        return $linked_items;
    }
 
    /**
     * @return BreadCrumb_BreadCrumbGenerator
     */
    public function getBreadcrumbs($plugin_path) {
        if ($this->milestone->getArtifact()) {
            $breadcrumbs_merger = new BreadCrumb_Merger();
            foreach(array_reverse($this->milestone->getAncestors()) as $milestone) {
                $breadcrumbs_merger->push(new BreadCrumb_Milestone($plugin_path, $milestone));
            }
            $breadcrumbs_merger->push(new BreadCrumb_Milestone($plugin_path, $this->milestone));
            return $breadcrumbs_merger;
        }
        return new BreadCrumb_NoCrumb();
    }

    private function getAllMilestonesOfCurrentPlanning() {
        if (!$this->all_milestones) {
            $this->all_milestones = $this->milestone_factory->getAllMilestones($this->getCurrentUser(), $this->milestone->getPlanning());
        }
        return $this->all_milestones;
    }
}

?>
