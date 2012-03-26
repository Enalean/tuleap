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

require_once 'common/plugin/Plugin.class.php';
require_once dirname(__FILE__) .'/../../tracker/include/Tracker/CrossSearch/ViewBuilder.class.php';
require_once dirname(__FILE__) .'/../../tracker/include/Tracker/TrackerFactory.class.php';
require_once dirname(__FILE__) .'/../../tracker/include/Tracker/FormElement/Tracker_FormElementFactory.class.php';
require_once dirname(__FILE__) .'/BreadCrumbs/BreadCrumbGenerator.class.php';

class AgileDashboardRouter {
    /**
     * @var Plugin
     */
    private $plugin;
    
    /**
     * @param Service
     */
    private $service;
    
    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
    }
    
    public function route($request) {
        $controller = $this->buildController($request);
        $artifact_plannification_controller = $this->buildArtifactPlannificationController($request);
        switch($request->get('action')) {
            case 'show':
                $object_god = new TrackerManager();
                $view_builder = $object_god->getCrossSearchViewBuilder();
                $this->renderAction($artifact_plannification_controller, 'show', $request, array($view_builder, ProjectManager::instance()));
                break;
            case 'new':
                $this->renderAction($controller, 'new_', $request);
                break;
            case 'create':
                $this->executeAction($controller, 'create');
                break;
            case 'delete':
                $this->executeAction($controller, 'delete');
                break;
            case 'index':
            default:
                $this->renderAction($controller, 'index', $request);
        }
    }
    
    private function getHeaderTitle($action_name) {
        $header_title = array(
            'index' => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_index'),
            'new_'  => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_new'),
            'show'  => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_show')
        );
        
        return $header_title[$action_name];
    }
    
    private function getService(Codendi_Request $request) {
        if ($this->service == null) {
            $project = ProjectManager::instance()->getProject($request->get('group_id'));
            $this->service = $project->getService('plugin_agiledashboard');
        }
        return $this->service;
    }
    
    private function displayHeader($controller, Codendi_Request $request, $title) {
        $breadcrumbs = $controller->getBreadcrumbs($this->plugin->getPluginPath());
        $this->getService($request)->displayHeader($title, $breadcrumbs->getCrumbs(), array());
    }
    
    
    private function displayFooter($request) {
        $this->getService($request)->displayFooter();
    }
    
    private function buildController($request) {
        require_once 'Planning/Controller.class.php';
        require_once 'Planning/PlanningFactory.class.php';
        require_once dirname(__FILE__) .'/../../tracker/include/Tracker/TrackerFactory.class.php';
        
        $planning_factory = new PlanningFactory(new PlanningDao(), TrackerFactory::instance());
        
        return new Planning_Controller($request, $planning_factory);
    }

    private function buildArtifactPlannificationController($request) {
        require_once 'Planning/ArtifactPlannificationController.class.php';
        require_once 'Planning/PlanningFactory.class.php';
        
        $artifact_factory = Tracker_ArtifactFactory::instance();
        $planning_factory = new PlanningFactory(new PlanningDao(), TrackerFactory::instance());
        return new Planning_ArtifactPlannificationController($request, $artifact_factory, $planning_factory);
    }
    
    private function renderAction(MVC2_Controller $controller, $action_name, Codendi_Request $request, array $args = array()) {
        $this->displayHeader($controller, $request, $this->getHeaderTitle($action_name));
        $this->executeAction($controller, $action_name, $args);
        $this->displayFooter($request);
    }
    
    private function executeAction(MVC2_Controller $controller, $action_name, array $args = array()) {
        call_user_func_array(array($controller, $action_name), $args);
    }
}
?>
