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
require_once 'RequestValidator.class.php';
require_once 'common/mvc2/Controller.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/Tracker/Planning/SearchContentView.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/Tracker/Artifact/Tracker_ArtifactFactory.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/Tracker/Artifact/Tracker_Artifact.class.php';
require_once dirname(__FILE__).'/../BreadCrumbs/AgileDashboard.class.php';

class Planning_Controller extends MVC2_Controller {
    
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    
    /**
     * @var PlanningFactory
     */
    private $planning_factory;
    
    /**
     * @var User
     */
    private $current_user;

    public function __construct(Codendi_Request $request, PlanningFactory $planning_factory) {
        parent::__construct('agiledashboard', $request);
        
        $this->group_id         = $request->get('group_id');
        $this->current_user     = $request->getCurrentUser();
        $this->planning_factory = $planning_factory;
    }
    
    public function index() {
        $plannings = $this->planning_factory->getPlannings($this->current_user, $this->group_id);
        $presenter = new Planning_IndexPresenter($plannings, $this->group_id);
        $this->render('index', $presenter);
    }
    
    public function new_() {
        $planning  = $this->planning_factory->buildNewPlanning($this->group_id);
        $presenter = $this->getFormPresenter($planning);
        
        $this->render('new', $presenter);
    }
    
    public function create() {
        $validator = new Planning_RequestValidator();
        
        if ($validator->isValid($this->request)) {
            $planning_name       = $this->request->get('planning_name');
            $group_id            = $this->group_id;
            $backlog_tracker_ids = $this->request->get('backlog_tracker_ids');
            $planning_tracker_id = $this->request->get('planning_tracker_id');
            
            $this->planning_factory->createPlanning($planning_name , $group_id, $backlog_tracker_ids, $planning_tracker_id);
            
            $this->redirect(array('group_id' => $group_id));
        } else {
            $this->addFeedback('error', $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_all_fields_mandatory'));
            $this->redirect(array('group_id' => $this->group_id, 'action' => 'new'));
        }
    }
    
    public function delete() {
        $this->planning_factory->deletePlanning($this->request->get('planning_id'));
        $this->redirect(array('group_id' => $this->group_id));
    }
    
    private function getFormPresenter(Planning $planning) {
        $available_trackers = $this->planning_factory->getAvailableTrackers($planning->getGroupId());
        return new Planning_FormPresenter($planning, $available_trackers);
    }
    
    private function getPlanning() {
        $planning_id = $this->request->get('planning_id');
        return $this->planning_factory->getPlanning($planning_id);
    }
    
    /**
     * @return BreadCrumb_BreadCrumbGenerator
     */
    public function getBreadcrumbs($plugin_path) {
        return new BreadCrumb_AgileDashboard($plugin_path, (int) $this->request->get('group_id'));
    }
}

?>
