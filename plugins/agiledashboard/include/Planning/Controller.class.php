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
require_once 'IndexPresenter.class.php';
require_once 'PlanningFactory.class.php';
require_once 'common/valid/ValidFactory.class.php';
require_once 'common/mustache/MustacheRenderer.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/Tracker/Artifact/Tracker_ArtifactFactory.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/Tracker/Artifact/Tracker_Artifact.class.php';

class Planning_CreatePresenter {
    /**
     * @var int
     */
    public $group_id;
    
    /**
     * @var TrackerFactory
     */
    public $tracker_factory;
    
    /**
     * @var Array of Tracker
     */
    private $available_trackers;
    
    public function __construct(/*int*/ $group_id, TrackerFactory $tracker_factory) {
        $this->group_id        = $group_id;
        $this->tracker_factory = $tracker_factory;
    }
    
    public function getAvailableTrackers() {
        if ($this->available_trackers == null) {
            $this->available_trackers = array_values($this->tracker_factory->getTrackersByGroupId($this->group_id));
        }
        return $this->available_trackers;
    }
}

class Planning_Controller {

    /**
     * @var Renderer
     */
    private $renderer;
    
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    
    function __construct(Codendi_Request $request, Tracker_ArtifactFactory $artifact_factory, PlanningFactory $planning_factory, TrackerFactory $tracker_factory) {
        $aid = $request->get('aid');
        $this->group_id = $request->get('group_id');
        $this->artifact = $artifact_factory->getArtifactById($aid);
        $this->renderer = new MustacheRenderer(dirname(__FILE__).'/../../templates');
        $this->planning_factory = $planning_factory;
        $this->tracker_factory  = $tracker_factory;
        $this->request = $request;
    }

    function display() {
        $presenter = new Planning_Presenter($this->artifact);
        $this->render('release-view', $presenter);
    }

    private function render($template_name, $presenter) {
        echo $this->renderer->render($template_name, $presenter);
    }
    
    public function index() {
        $presenter = new Planning_IndexPresenter ($this->planning_factory, $this->group_id);
        $this->render('index', $presenter);
    }
    
    public function create() {
        $presenter = new Planning_CreatePresenter($this->group_id, $this->tracker_factory);
        $this->render('create', $presenter);
    }
    
    public function doCreate() {
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
                                            $this->request->get('planning_backlog_ids'),
                                            $this->request->get('planning_release_id'));
            
            $this->redirect(array('group_id' => $this->group_id));
        } else {
            $GLOBALS['Response']->addFeedback('error', 'All fields are mandatory');
            $this->redirect(array('group_id' => $this->group_id,
                                  'func'     => 'create'));
        }
    }
    
    private function redirect($query_parts) {
        $redirect = http_build_query($query_parts);
        $GLOBALS['Response']->redirect('/plugins/agiledashboard/?'.$redirect);
    }
}
?>
