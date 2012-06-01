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
require_once dirname(__FILE__).'/../BreadCrumbs/AgileDashboard.class.php';
require_once dirname(__FILE__).'/../BreadCrumbs/Artifact.class.php';
require_once dirname(__FILE__).'/../BreadCrumbs/Planning.class.php';
require_once dirname(__FILE__).'/../BreadCrumbs/Merger.class.php';
require_once 'SearchContentView.class.php';
require_once 'MilestonePresenter.class.php';
require_once 'MilestoneFactory.class.php';

/**
 * Handles the HTTP actions related to a planning milestone.
 */
class Planning_MilestoneController extends MVC2_Controller {
    
    /**
     * @var Tracker_ArtifactFactory
     * 
     * TODO: Use $milestone_factory instead, which should delegate to
     *       Tracker_ArtifactFactory.
     */
    private $artifact_factory;
    
    /**
     * @var PlanningFactory
     * 
     * TODO: Use $milestone_factory instead, which should delegate to
     *       Planning_Factory.
     */
    private $planning_factory;
    
    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;
    
    /**
     * @var Planning_Milestone
     */
    private $milestone;
    
    /**
     * Instanciates a new controller.
     * 
     * TODO:
     *   - $artifact_factory must die
     *   - $tracker_factory must die
     *   - pass $request to actions (e.g. show).
     * 
     * @param Codendi_Request           $request
     * @param Tracker_ArtifactFactory   $artifact_factory
     * @param PlanningFactory           $planning_factory
     * @param Planning_MilestoneFactory $milestone_factory 
     */
    public function __construct(Codendi_Request           $request,
                                Tracker_ArtifactFactory   $artifact_factory,
                                PlanningFactory           $planning_factory,
                                Planning_MilestoneFactory $milestone_factory) {
        
        parent::__construct('agiledashboard', $request);
        
        $this->artifact_factory  = $artifact_factory;
        $this->planning_factory  = $planning_factory;
        $this->milestone_factory = $milestone_factory;
        $this->milestone         = $this->milestone_factory->getMilestoneWithPlannedArtifactsAndSubMilestones(
                                       $this->getCurrentUser(),
                                       $request->get('group_id'),
                                       $request->get('planning_id'),
                                       $request->get('aid')
                                   );
    }

    public function show(Planning_ViewBuilder $view_builder, ProjectManager $manager) {
        $project              = $manager->getProject($this->milestone->getGroupId());
        $planning             = $this->getPlanning();
        $available_milestones = $this->artifact_factory->getOpenArtifactsByTrackerIdUserCanView($this->getCurrentUser(), $planning->getPlanningTrackerId());
        $backlog_tracker_id   = $planning->getBacklogTrackerId();
        
        $content_view         = $this->buildContentView($view_builder, $project, $backlog_tracker_id, $available_milestones, $planning);
        $presenter            = $this->getMilestonePresenter($planning, $content_view, $available_milestones);
        
        $this->render('show', $presenter);
    }

    private function getMilestonePresenter(Planning                              $planning,
                                           Tracker_CrossSearch_SearchContentView $content_view,
                                           array                                 $available_milestones) {
        
        $planning_redirect_parameter = $this->getPlanningRedirectParameter();
        
        return new Planning_MilestonePresenter($planning,
                                               $content_view,
                                               $available_milestones,
                                               $this->milestone,
                                               $this->getCurrentUser(),
                                               $planning_redirect_parameter);
    }
    
    private function getPlanningRedirectParameter() {
        $planning_id = (int) $this->milestone->getPlanningId();
        $artifact_id = $this->milestone->getArtifactId();
        
        return "planning[$planning_id]=$artifact_id";
    }
    
    private function getCrossSearchQuery() {
        $request_criteria      = $this->getArrayFromRequest('criteria');
        $semantic_criteria     = $this->getArrayFromRequest('semantic_criteria');
        $artifact_criteria     = $this->getArrayFromRequest('artifact_criteria');
        
        return new Tracker_CrossSearch_Query($request_criteria, $semantic_criteria, $artifact_criteria);
    }
    
    private function buildContentView(Planning_ViewBuilder $view_builder,
                                      Project              $project = null,
                                                           $backlog_tracker_id,
                                      array                $available_milestones,
                                      Planning             $planning) {
        
        $tracker_linked_items  = $this->getTrackerLinkedItems($available_milestones);
        $excluded_artifact_ids = array_map(array($this, 'getArtifactId'), $tracker_linked_items);
        $cross_search_query    = $this->getCrossSearchQuery();
        $tracker_ids           = $backlog_tracker_id ? array($backlog_tracker_id) : array();
        
        $view = $view_builder->build($this->getCurrentUser(), 
                                                 $project, 
                                                 $cross_search_query, 
                                                 $excluded_artifact_ids, 
                                                 $tracker_ids,
                                                 $planning,
                                                 $this->getPlanningRedirectParameter($planning));
        
        return $view;
    }

    private function getArtifactId(Tracker_Artifact $artifact) {
        return $artifact->getId();
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
    
    /**
     * @param array of Tracker_Artifact $available_milestones
     * 
     * @return array of Tracker_Artifact
     */
    private function getTrackerLinkedItems(array $available_milestones) {
        $linked_items = array();
        foreach ($available_milestones as $artifact) {
            $linked_items = array_merge($linked_items, $artifact->getLinkedArtifacts($this->getCurrentUser()));
        }
        return $linked_items;
    }
 
    /**
     * @return Planning
     */
    private function getPlanning() {
        $planning_id = $this->request->get('planning_id');
        return $this->planning_factory->getPlanningWithTrackers($planning_id);
    }

    /**
     * @return BreadCrumb_BreadCrumbGenerator
     */
    public function getBreadcrumbs($plugin_path) {
        $base_breadcrumbs_generator      = new BreadCrumb_AgileDashboard($plugin_path, $this->milestone->getGroupId());
        $planning_breadcrumbs_generator  = new BreadCrumb_Planning($plugin_path, $this->getPlanning());
        $artifacts_breadcrumbs_generator = new BreadCrumb_Artifact($plugin_path, $this->milestone->getArtifact());
        return new BreadCrumb_Merger($base_breadcrumbs_generator, $planning_breadcrumbs_generator, $artifacts_breadcrumbs_generator);
    }
}

?>
