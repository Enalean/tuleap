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
require_once 'ArtifactPlannificationPresenter.class.php';

class Planning_ArtifactPlannificationController extends MVC2_Controller {
    
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    
    public function __construct(Codendi_Request $request, Tracker_ArtifactFactory $artifact_factory, PlanningFactory $planning_factory) {
        parent::__construct('agiledashboard', $request);
        
        $aid                    = $request->get('aid');
        $this->group_id         = $request->get('group_id');
        $this->artifact         = $artifact_factory->getArtifactById($aid);
        $this->artifact_factory = $artifact_factory;
        $this->planning_factory = $planning_factory;
    }

    public function show(Planning_ViewBuilder $view_builder, ProjectManager $manager) {
        $planning            = $this->getPlanning();
        $project_id          = $this->request->get('group_id');
        $artifacts_to_select = $this->artifact_factory->getOpenArtifactsByTrackerId($planning->getPlanningTrackerId());
        $tracker_ids         = $planning->getBacklogTrackerIds();
        
        $content_view        = $this->buildContentView($view_builder, $manager->getProject($project_id), $tracker_ids, $artifacts_to_select, $planning);
        
        $presenter           = $this->getShowPresenter($planning, $content_view, $artifacts_to_select, $this->artifact);
        $this->render('show', $presenter);
    }

    public function getShowPresenter(Planning $planning,
                                     Tracker_CrossSearch_SearchContentView $content_view,
                                     array $artifacts_to_select,
                                     Tracker_Artifact $artifact = null) {
        
        $planning_redirect_parameter = $this->getPlanningRedirectParameter($planning);
        
        return new Planning_ArtifactPlanificationPresenter($planning, $content_view, $artifacts_to_select, $artifact, $this->getCurrentUser(), $planning_redirect_parameter);
    }
    
    private function getPlanningRedirectParameter(Planning $planning) {
        $planning_redirect_parameter = 'planning['. (int)$planning->getId() .']=';
        if ($this->artifact) {
            $planning_redirect_parameter .= $this->artifact->getId();
        }
        return $planning_redirect_parameter;
    }
    
    private function getCrossSearchQuery() {
        $request_criteria      = $this->getArrayFromRequest('criteria');
        $semantic_criteria     = $this->getArrayFromRequest('semantic_criteria');
        $artifact_criteria     = $this->getArrayFromRequest('artifact_criteria');
        return new Tracker_CrossSearch_Query($request_criteria, $semantic_criteria, $artifact_criteria);
    }
    
    private function buildContentView(Planning_ViewBuilder $view_builder, $project, array $tracker_ids, array $artifacts_to_select, Planning $planning) {
        $tracker_linked_items  = $this->getTrackerLinkedItems($artifacts_to_select);
        $excluded_artifact_ids = array_map(array($this, 'getArtifactId'), $tracker_linked_items);
        $cross_search_query    = $this->getCrossSearchQuery();

        
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
    
    private function getTrackerLinkedItems($artifacts_to_select) {
        $linked_items = array();
        foreach ($artifacts_to_select as $artifact) {
            $linked_items = array_merge($linked_items, $artifact->getLinkedArtifacts($this->getCurrentUser()));
        }
        return $linked_items;
    }
 
    
    private function getPlanning() {
        $planning_id = $this->request->get('planning_id');
        return $this->planning_factory->getPlanningWithTrackers($planning_id);
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
