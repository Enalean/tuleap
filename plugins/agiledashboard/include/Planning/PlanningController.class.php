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
 
require_once 'common/mvc2/PluginController.class.php';

/**
 * Handles the HTTP actions related to a planning.
 * 
 * TODO: Rename this file to PlanningController.class.php, to be consistent with
 * other classes. 
 */
class Planning_Controller extends MVC2_PluginController {
    
    /**
     * @var PlanningFactory
     */
    private $planning_factory;
    
    public function __construct(
        Codendi_Request $request,
        PlanningFactory $planning_factory,
        Planning_ShortAccessFactory $planning_shortaccess_factory,
        Planning_MilestoneFactory $milestone_factory,
        $plugin_theme_path
    ) {
        parent::__construct('agiledashboard', $request);
        
        $this->group_id                     = (int)$request->get('group_id');
        $this->planning_factory             = $planning_factory;
        $this->planning_shortaccess_factory = $planning_shortaccess_factory;
        $this->milestone_factory            = $milestone_factory;
        $this->plugin_theme_path            = $plugin_theme_path;
    }
    
    public function admin() {
        $plannings = $this->planning_factory->getPlannings($this->getCurrentUser(), $this->group_id);
        $presenter = new Planning_ListPresenter($plannings, $this->group_id);
        $this->render('admin', $presenter);
    }
    
    public function index() {
        $project_id = $this->request->getProject()->getID();
        $plannings = $this->getPlanningsShortAccess($this->group_id);
        $presenter = new Planning_IndexPresenter(
            $plannings,
            $this->plugin_theme_path,
            $project_id,
            $this->request->getCurrentUser()->useLabFeatures()
        );
        $this->render('index', $presenter);
    }
    
    public function new_() {
        $planning  = $this->planning_factory->buildNewPlanning($this->group_id);
        $presenter = $this->getFormPresenter($planning);
        
        $this->render('new', $presenter);
    }
    
    public function create() {
        $this->checkUserIsAdmin();
        $validator = new Planning_RequestValidator($this->planning_factory);
        
        if ($validator->isValid($this->request)) {
            $this->planning_factory->createPlanning($this->group_id,
                                                    PlanningParameters::fromArray($this->request->get('planning')));
            
            $this->redirect(array('group_id' => $this->group_id));
        } else {
            // TODO: Error message should reflect validation detail
            $this->addFeedback('error', $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_all_fields_mandatory'));
            $this->redirect(array('group_id' => $this->group_id, 'action' => 'new'));
        }
    }
    
    public function edit() {
        $planning  = $this->planning_factory->getPlanningWithTrackers($this->request->get('planning_id'));
        $presenter = $this->getFormPresenter($planning);
        
        $this->render('edit', $presenter);
    }
    
    public function update() {
        $this->checkUserIsAdmin();
        $validator = new Planning_RequestValidator($this->planning_factory);
        
        if ($validator->isValid($this->request)) {
            $this->planning_factory->updatePlanning($this->request->get('planning_id'),
                                                    PlanningParameters::fromArray($this->request->get('planning')));
        
            $this->redirect(array('group_id' => $this->request->get('group_id'),
                                  'action'   => 'index'));
        } else {
            $this->addFeedback('error', $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_all_fields_mandatory'));
            $this->redirect(array('group_id'    => $this->group_id,
                                  'planning_id' => $this->request->get('planning_id'),
                                  'action'      => 'edit'));
        }
    }
    
    public function delete() {
        $this->checkUserIsAdmin();
        $this->planning_factory->deletePlanning($this->request->get('planning_id'));
        $this->redirect(array('group_id' => $this->group_id));
    }

    /**
     * @return BreadCrumb_BreadCrumbGenerator
     */
    public function getBreadcrumbs($plugin_path) {
        return new BreadCrumb_AgileDashboard();
    }

    public function generateSystrayData() {
        $user  = $this->request->get('user');
        $links = $this->request->get('links');
        
        foreach ($user->getGroups() as $project) {
            if (! $project->usesService('plugin_agiledashboard')) {
                continue;
            }

            $plannings = $this->getPlanningsShortAccess($project->getID());

            /* @var $links Systray_LinksCollection */
            $links->append(
                new Systray_AgileDashboardLink($project, $plannings)
            );
        }
    }

    /**
     *
     * @param int $projectId
     * @return Planning_ShortAccess[]
     */
    private function getPlanningsShortAccess($projectId) {
        return $this->planning_shortaccess_factory->getPlanningsShortAccess(
            $this->getCurrentUser(),
            $projectId,
            $this->milestone_factory,
            $this->plugin_theme_path
        );
    }

    private function getFormPresenter(Planning $planning) {
        $group_id = $planning->getGroupId();
        
        $available_trackers          = $this->planning_factory->getAvailableTrackers($group_id);
        $available_planning_trackers = $this->planning_factory->getAvailablePlanningTrackers($planning);
        
        return new Planning_FormPresenter($planning, $available_trackers, $available_planning_trackers);
    }
    
    private function getPlanning() {
        $planning_id = $this->request->get('planning_id');
        return $this->planning_factory->getPlanning($planning_id);
    }
}

?>
