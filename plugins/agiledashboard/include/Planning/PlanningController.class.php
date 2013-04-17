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
     * @var Tracker_Artifact
     */
    private $artifact;
    
    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    public function __construct(Codendi_Request $request, PlanningFactory $planning_factory, Planning_MilestoneFactory $milestone_factory, $plugin_theme_path) {
        parent::__construct('agiledashboard', $request);
        
        $this->group_id          = (int)$request->get('group_id');
        $this->planning_factory  = $planning_factory;
        $this->milestone_factory = $milestone_factory;
        $this->plugin_theme_path = $plugin_theme_path;
    }
    
    public function admin() {
        $plannings = $this->planning_factory->getPlannings($this->getCurrentUser(), $this->group_id);
        $presenter = new Planning_ListPresenter($plannings, $this->group_id);
        $this->render('admin', $presenter);
    }
    
    public function index() {
        $plannings = $this->planning_factory->getPlanningsShortAccess($this->getCurrentUser(), $this->group_id, $this->milestone_factory, $this->plugin_theme_path);
        $presenter = new Planning_IndexPresenter($plannings, $this->plugin_theme_path);
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

    public function toggleUserDisplay() {
        $pref_name  = 'AD_cardwall_assign_to_display_type';
        $user       = $this->getCurrentUser();
        $preference = $user->getPreference($pref_name);

        if(! $preference) {
            $user->setPreference($pref_name, 'username');
        } else {
            $this->switchPreference($user, $preference);
        }

        $this->redirect(array(
            'group_id'    => $this->group_id,
            'planning_id' => $this->request->get('planning_id'),
            'action'      => 'show',
            'pane'        => $this->request->get('pane')
        ));
    }

    private function switchPreference($user, $preference) {
        $pref_name  = 'AD_cardwall_assign_to_display_type';
        $pref_value = 'username';
        if($preference == 'username') {
            $pref_value = 'avatar';
        }

        $user->setPreference($pref_name, $pref_value);
    }

    /**
     *
     * @param int $projectId
     * @return Planning_ShortAccess[]
     */
    private function getPlanningsShortAccess($projectId) {
        return $this->planning_factory->getPlanningsShortAccess(
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

    private function checkUserIsAdmin() {
        $project = $this->request->getProject();
        $user    = $this->request->getCurrentUser();
        if (! $project->userIsAdmin($user)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'perm_denied'));
            $this->redirect(array('group_id' => $this->group_id));
            // the below is only run by tests (redirect should exit but is mocked)
            throw new Exception($GLOBALS['Language']->getText('global', 'perm_denied'));
        }
    }
}

?>
