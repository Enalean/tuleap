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

class Planning_ArtifactPlannificationController extends MVC2_Controller {
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    
    /**
     * @var User
     */
    private $current_user;
    
    public function __construct(Codendi_Request $request, Tracker_ArtifactFactory $artifact_factory, PlanningFactory $planning_factory) {
        parent::__construct('agiledashboard', $request);
        
        $aid                    = $request->get('aid');
        $this->group_id         = $request->get('group_id');
        $this->artifact         = $artifact_factory->getArtifactById($aid);
        $this->artifact_factory = $artifact_factory;
        $this->planning_factory = $planning_factory;
        $this->current_user     = $request->getCurrentUser();
        $this->current_uri      = $request->getUri();
    }

    private function getArtifactId(Tracker_Artifact $artifact) {
        return $artifact->getId();
    }

    public function getShowPresenter(Planning $planning,
                                     Tracker_CrossSearch_SearchContentView $content_view,
                                     array $artifacts_to_select,
                                     Tracker_Artifact $artifact = null) {
        return new Planning_ShowPresenter($planning, $content_view, $artifacts_to_select, $artifact, $this->current_user, $this->current_uri);
    }
    
    public function show(Tracker_CrossSearch_ViewBuilder $view_builder, ProjectManager $manager) {
        $planning            = $this->getPlanning();
        $project_id          = $this->request->get('group_id');
        $artifacts_to_select = $this->artifact_factory->getOpenArtifactsByTrackerId($planning->getPlanningTrackerId());
        $tracker_ids         = $planning->getBacklogTrackerIds();
        
        $content_view        = $this->buildContentView($view_builder, $manager->getProject($project_id), $tracker_ids, $artifacts_to_select);
        
        $presenter           = $this->getShowPresenter($planning, $content_view, $artifacts_to_select, $this->artifact);
        $this->render('show', $presenter);
    }

    private function getCrossSearchQuery() {
        $request_criteria      = $this->getArrayFromRequest('criteria');
        $semantic_criteria     = $this->getArrayFromRequest('semantic_criteria');
        $artifact_criteria     = $this->getArrayFromRequest('artifact_criteria');
        return new Tracker_CrossSearch_Query($request_criteria, $semantic_criteria, $artifact_criteria);
    }
    
    private function buildContentView(Tracker_CrossSearch_ViewBuilder $view_builder, $project, array $tracker_ids, array $artifacts_to_select) {
        
        $tracker_linked_items  = $this->getTrackerLinkedItems($artifacts_to_select);
        $excluded_artifact_ids = array_map(array($this, 'getArtifactId'), $tracker_linked_items);
        $cross_search_query    = $this->getCrossSearchQuery();
        return $view_builder->buildCustomContentView('Planning_SearchContentView', $this->current_user, $project, $cross_search_query, $excluded_artifact_ids, $tracker_ids);
       
    }
    
    private function getArrayFromRequest($parameter_name) {
        $request_criteria = array();
        $valid_criteria = new Valid_Array($parameter_name);
        $valid_criteria->required();
        if ($this->request->valid($valid_criteria)) {
            $request_criteria = $this->request->get($parameter_name);
        }
        return $request_criteria;
    }
    
    private function getTrackerLinkedItems($artifacts_to_select) {
        $linked_items = array();
        foreach ($artifacts_to_select as $artifact) {
            $linked_items = array_merge($linked_items, $artifact->getLinkedArtifacts($this->current_user));
        }
        return $linked_items;
    }
 
    
    private function getPlanning() {
        $planning_id = $this->request->get('planning_id');
        return $this->planning_factory->getPlanningWithPlanningTracker($planning_id);
    }

    /**
     * @return BreadCrumb_BreadCrumbGenerator
     */
    public function getBreadcrumbs($plugin_path) {
        $base_breadcrumbs_generator      = new BreadCrumb_AgileDashboard($plugin_path, (int) $this->request->get('group_id'));
        $planning_breadcrumbs_generator  = new BreadCrumb_Planning($plugin_path, $this->getPlanning());
        $artifacts_breadcrumbs_generator = new BreadCrumb_Artifact($plugin_path, $this->artifact);
        return new BreadCrumb_Merger($base_breadcrumbs_generator, $planning_breadcrumbs_generator, $artifacts_breadcrumbs_generator);
    }
}

?>
