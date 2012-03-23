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
require_once dirname(__FILE__).'/../AgileDashBoardPluginBreadCrumb.class.php';
require_once dirname(__FILE__).'/../AgileDashBoardPluginArtifactBreadCrumb.class.php';

class Planning_ArtifactPlannificationController extends MVC2_Controller {
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    
    public function __construct(Codendi_Request $request, Tracker_ArtifactFactory $artifact_factory, PlanningFactory $planning_factory) {
        parent::__construct('agiledashboard', $request);
        
        $aid = $request->get('aid');
        $this->group_id = $request->get('group_id');
        $this->artifact = $artifact_factory->getArtifactById($aid);
        $this->artifact_factory = $artifact_factory;
        $this->planning_factory = $planning_factory;
    }

    private function getArtifactId(Tracker_Artifact $artifact) {
        return $artifact->getId();
    }

    public function show(Tracker_CrossSearch_ViewBuilder $view_builder, ProjectManager $manager) {
        $planning = $this->getPlanning();
        $artifacts_to_select = $this->artifact_factory->getOpenArtifactsByTrackerId($planning->getPlanningTrackerId());

        $content_view        = $this->buildContentView($view_builder, $manager, $planning, $artifacts_to_select);
        $presenter           = new Planning_ShowPresenter($planning, $content_view, $artifacts_to_select, $this->artifact);
        $this->render('show', $presenter);
    }

    private function buildContentView(Tracker_CrossSearch_ViewBuilder $view_builder, ProjectManager $manager, Planning $planning, $artifacts_to_select) {
        $project  = $manager->getProject($this->request->get('group_id'));
        $request_criteria = $this->getCriteriaFromRequest();
        $excludedArtifactIds = array_map(array($this, 'getArtifactId'),$this->getTrackerLinkedItems($artifacts_to_select));
        $tracker_ids = $planning->getBacklogTrackerIds();
        return $view_builder->buildPlanningContentView($project, $request_criteria, $excludedArtifactIds, $tracker_ids);
    }
    
    private function getCriteriaFromRequest() {
        $request_criteria = array();
        $valid_criteria = new Valid_Array('criteria');
        $valid_criteria->required();
        if ($this->request->valid($valid_criteria)) {
            $request_criteria = $this->request->get('criteria');
        }
        return $request_criteria;
    }
    
    private function getTrackerLinkedItems($artifacts_to_select) {
        $linked_items = array();
        foreach ($artifacts_to_select as $artifact) {
            $linked_items = array_merge($linked_items, $artifact->getLinkedArtifacts());
        }
        return $linked_items;
    }
 
    
    private function getPlanning() {
        $planning_id = $this->request->get('planning_id');
        return $this->planning_factory->getPlanning($planning_id);
    }

    
    public function getBreadcrumbs($plugin_path) {
        $breadcrumb = new AgileDashBoardPluginBreadCrumb((int) $this->request->get('group_id'), $plugin_path);
        $breadcrumbs = $breadcrumb->getCrumbs();
        $artifactsBc   = new AgileDashBoardPluginArtifactBreadCrumb($plugin_path, $this->artifact, $this->getPlanning());
        $artifacts    = $artifactsBc->getCrumbs();
        return array_merge($breadcrumbs, $artifacts);
    }
    
}

?>
