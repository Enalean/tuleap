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

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var Planning_ShortAccessFactory */
    private $planning_shortaccess_factory;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var String*/
    private $plugin_theme_path;

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
        return $this->renderToString('admin', $presenter);
    }
    
    public function index() {
        try {
            $project_id = $this->request->getProject()->getID();
            $plannings = $this->getPlanningsShortAccess($this->group_id);
        } catch (Planning_InvalidConfigurationException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
            $plannings = array();
        }
        $presenter = new Planning_IndexPresenter(
            $plannings,
            $this->plugin_theme_path,
            $project_id
        );
        return $this->renderToString('index', $presenter);
    }
    
    public function new_() {
        $planning  = $this->planning_factory->buildNewPlanning($this->group_id);
        $presenter = $this->getFormPresenter($planning);
        
        return $this->renderToString('new', $presenter);
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
        $planning  = $this->planning_factory->getPlanning($this->request->get('planning_id'));
        $presenter = $this->getFormPresenter($planning);
        
        return $this->renderToString('edit', $presenter);
    }
    
    private function getFormPresenter(Planning $planning) {
        $group_id = $planning->getGroupId();

        $available_trackers          = $this->planning_factory->getAvailableTrackers($group_id);
        $available_planning_trackers = $this->planning_factory->getAvailablePlanningTrackers($planning);
        $cardwall_admin              = $this->getCardwallConfiguration($planning);

        return new Planning_FormPresenter($planning, $available_trackers, $available_planning_trackers, $cardwall_admin);
    }

    private function getCardwallConfiguration(Planning $planning) {
        $tracker  = $planning->getPlanningTracker();
        $view     = null;

        EventManager::instance()->processEvent(
            AGILEDASHBOARD_EVENT_PLANNING_CONFIG,
            array(
                'tracker' => $tracker,
                'view'    => &$view,
            )
        );

        return $view;
    }

    public function update() {
        $this->checkUserIsAdmin();
        $validator = new Planning_RequestValidator($this->planning_factory);
        
        if ($validator->isValid($this->request)) {
            $this->planning_factory->updatePlanning($this->request->get('planning_id'),
                                                    PlanningParameters::fromArray($this->request->get('planning')));
        } else {
            $this->addFeedback('error', $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_all_fields_mandatory'));
        }

        $this->updateCardwallConfig();

        $this->redirect(array('group_id'    => $this->group_id,
                              'planning_id' => $this->request->get('planning_id'),
                              'action'      => 'edit'));
    }

    private function updateCardwallConfig() {
        $tracker = $this->getPlanning()->getPlanningTracker();

        EventManager::instance()->processEvent(
            AGILEDASHBOARD_EVENT_PLANNING_CONFIG_UPDATE,
            array(
                'request' => $this->request,
                'tracker' => $tracker,
            )
        );
    }

    public function delete() {
        $this->checkUserIsAdmin();
        $this->planning_factory->deletePlanning($this->request->get('planning_id'));
        return $this->redirect(array('group_id' => $this->group_id));
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

    public function getMoreMilestones() {
        $offset = $this->request->get('offset', 'uint', 0);
        $planning = $this->planning_factory->getPlanning($this->request->get('planning_id'));
        $short_access = $this->planning_shortaccess_factory->getShortAccessForPlanning(
            $planning,
            $this->getCurrentUser(),
            $this->milestone_factory,
            $this->plugin_theme_path,
            $offset
        );

        $this->render('shortaccess-milestones', $short_access);
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
    
    private function getPlanning() {
        $planning_id = $this->request->get('planning_id');
        return $this->planning_factory->getPlanning($planning_id);
    }
}

?>
