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
 
require_once 'ShowPresenter.class.php';
require_once 'FormPresenter.class.php';
require_once 'IndexPresenter.class.php';
require_once 'PlanningFactory.class.php';
require_once 'NotFoundException.class.php';
require_once 'common/valid/ValidFactory.class.php';
require_once 'common/mvc2/Controller.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/Tracker/Planning/SearchContentView.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/Tracker/Artifact/Tracker_ArtifactFactory.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/Tracker/Artifact/Tracker_Artifact.class.php';

class Planning_Controller extends Controller {
    
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    
    function __construct(Codendi_Request $request, Tracker_ArtifactFactory $artifact_factory, PlanningFactory $planning_factory, TrackerFactory $tracker_factory) {
        parent::__construct('agiledashboard', $request);
        
        $aid = $request->get('aid');
        $this->group_id = $request->get('group_id');
        $this->artifact = $artifact_factory->getArtifactById($aid);
        $this->artifact_factory = $artifact_factory;
        $this->planning_factory = $planning_factory;
        $this->tracker_factory  = $tracker_factory;
    }
    
    public function index() {
        $presenter = new Planning_IndexPresenter ($this->planning_factory, $this->group_id);
        $this->render('index', $presenter);
    }
    
    public function new_() {
        $presenter = new Planning_FormPresenter($this->group_id, $this->tracker_factory, null);
        $this->render('new', $presenter);
    }
    
    public function create() {
        $planning_name = new Valid_String('planning_name');
        $planning_name->required();
        
        $planning_backlog_ids = new Valid_UInt('planning_backlog_ids');
        $planning_backlog_ids->required();
        
        $planning_release_id = new Valid_UInt('planning_release_id');
        $planning_release_id->required();
        
        if ($this->request->validArray($planning_backlog_ids) && 
            $this->request->valid($planning_release_id) &&
            $this->request->valid($planning_name)) {
            
            $this->planning_factory->create($this->request->get('planning_name'),
                                            $this->group_id,
                                            $this->request->get('planning_backlog_ids'),
                                            $this->request->get('planning_release_id'));
            
            $this->redirect(array('group_id' => $this->group_id));
        } else {
            $this->addFeedback('error', 'All fields are mandatory');
            $this->redirect(array('group_id' => $this->group_id,
                                  'action'   => 'new'));
        }
    }
    
    private function getArtifactId(Tracker_Artifact $artifact) {
        return $artifact->getId();
    }

    function show(Tracker_CrossSearch_ViewBuilder $view_builder, ProjectManager $manager) {
        $planning = $this->getPlanning();
        $artifacts_to_select = $this->artifact_factory->getOpenArtifactsByTrackerId($planning->getReleaseTrackerId());

        $content_view        = $this->buildContentView($view_builder, $manager, $planning, $artifacts_to_select);
        $presenter           = new Planning_ShowPresenter($planning, $content_view, $artifacts_to_select, $this->artifact);
        $this->render('show', $presenter);
    }

    public function buildContentView($view_builder, $manager, $planning, $artifacts_to_select) {
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
    
    public function edit() {
        try {
            $planning = $this->getPlanning();
            $presenter = new Planning_FormPresenter($this->group_id, $this->tracker_factory, $planning);
            $this->render('edit', $presenter);
            
        } catch(Planning_NotFoundException $exception) {
            $GLOBALS['Response']->sendStatusCode(404);
        }
    }
    
    public function update() {
        $this->planning_factory->updatePlanning($this->request->get('planning_id'),
                                                $this->request->get('planning_name'),
                                                $this->request->get('planning_backlog_ids'),
                                                $this->request->get('planning_release_id'));
        $this->redirect(array('group_id' => $this->group_id));
    }
    
    public function delete() {
        $this->planning_factory->deletePlanning($this->request->get('planning_id'));
        $this->redirect(array('group_id' => $this->group_id));
    }
    
    private function getPlanning() {
        $planning_id = $this->request->get('planning_id');
        return $this->planning_factory->getPlanning($planning_id);
    }
    
    public function getBreadcrumbs($plugin_path) {
        $hp             = Codendi_HTMLPurifier::instance();
        $breadcrumbs    = array();
        $url_parameters = array(
            'group_id' => (int) $this->request->get('group_id'),
        );
        
        $breadcrumbs[] = array(
            'url'   => $plugin_path .'/?'. http_build_query($url_parameters),
            'title' => $GLOBALS['Language']->getText('plugin_agiledashboard', 'service_lbl_key')
        );
        $planning = $this->getPlanning();
        if ($planning) {
            $url_parameters['planning_id'] = (int) $planning->getId();
            $url_parameters['action']      = 'show';
            $breadcrumbs[] = array(
                'url'   => $plugin_path .'/?'. http_build_query($url_parameters),
                'title' => $hp->purify($planning->getName()),
            );
            if ($this->artifact) {
                $url_parameters['aid'] = (int) $this->artifact->getId();
                $breadcrumbs[] = array(
                    'url'   => $plugin_path .'/?'. http_build_query($url_parameters),
                    'title' => $hp->purify($this->artifact->getTitle()),
                );
            }
        }
        return $breadcrumbs;
    }
}
?>
