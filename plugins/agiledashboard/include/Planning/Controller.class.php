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
 
require_once 'Presenter.class.php';
require_once 'FormPresenter.class.php';
require_once 'IndexPresenter.class.php';
require_once 'PlanningFactory.class.php';
require_once 'SearchContentView.class.php';
require_once 'NotFoundException.class.php';
require_once 'common/valid/ValidFactory.class.php';
require_once 'common/mvc2/Controller.class.php';
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
//        $planning = new Planning(null, '');
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

    function show(Tracker_CrossSearch_ViewBuilder $view_builder, ProjectManager $manager, Tracker_CrossSearch_Search $search) {
        $request_criteria = array();
        $valid_criteria = new Valid_Array('criteria');
        $valid_criteria->required();
        if ($this->request->valid($valid_criteria)) {
            $request_criteria = $this->request->get('criteria');
        }
        $planning_id         = $this->request->get('planning_id');
        $planning            = $this->planning_factory->getPlanning($planning_id);
        $project             = $manager->getProject($this->request->get('group_id'));
        
        $excludedArtifactIds = array();
        if ($this->artifact) {
            $excludedArtifacts = $this->artifact->getLinkedArtifacts();
            $excludedArtifactIds = array_map(array($this, 'getArtifactId'), $excludedArtifacts);
        }
        
        $content_view        = $view_builder->buildCustomContentView('Planning_SearchContentView', $project, $request_criteria, $search, $excludedArtifactIds);
        $artifacts_to_select = $this->artifact_factory->getOpenArtifactsByTrackerId($planning->getReleaseTrackerId());
        $presenter           = new Planning_Presenter($planning, $content_view, $artifacts_to_select, $this->artifact);
        $this->render('show', $presenter);
    }
    
    public function edit() {
        try {
            $planning_id = $this->request->get('planning_id');
            $planning    = $this->planning_factory->getPlanning($planning_id);
            //$presenter   = array();
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
}
?>
