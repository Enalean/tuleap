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

/**
 * AgileDashboardPlugin
 */
class AgileDashboardPlugin extends Plugin {

    private $service;
    
    /**
     * Plugin constructor
     */
    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        // Do not load the plugin if tracker is not installed & active
        if (defined('TRACKER_BASE_URL')) {
            $this->_addHook('cssfile', 'cssfile', false);
            $this->_addHook(Event::COMBINED_SCRIPTS, 'combined_scripts', false);
            $this->_addHook(TRACKER_EVENT_INCLUDE_CSS_FILE, 'tracker_event_include_css_file', false);
        }
    }
    
    public function tracker_event_include_css_file($params) {
        $params['include_tracker_css_file'] = true;
    }
    
    /**
     * @return AgileDashboardPluginInfo
     */
    public function getPluginInfo() {
        if (!$this->pluginInfo) {
            include_once 'AgileDashboardPluginInfo.class.php';
            $this->pluginInfo = new AgileDashboardPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    public function cssfile($params) {
        // Only show the stylesheet if we're actually in the AgileDashboard pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }
    
    public function combined_scripts($params) {
        $params['scripts'] = array_merge(
            $params['scripts'],
            array(
                $this->getPluginPath().'/js/drag-n-drop.js',
                $this->getPluginPath().'/js/planning-view.js',
            )
        );
    }
    
    public function process(Codendi_Request $request) {
        require_once dirname(__FILE__) .'/../../tracker/include/Tracker/CrossSearch/ViewBuilder.class.php';
        require_once dirname(__FILE__) .'/../../tracker/include/Tracker/TrackerFactory.class.php';
        require_once dirname(__FILE__) .'/../../tracker/include/Tracker/FormElement/Tracker_FormElementFactory.class.php';
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
            case 'edit':
                $this->renderAction($controller, 'edit', $request);
                break;
            case 'update':
                $this->executeAction($controller, 'update');
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
            'edit'  => $GLOBALS['Language']->getText('plugin_agiledashboard', 'planning_edit'),
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
        $breadcrumbs = $controller->getBreadcrumbs($this->getPluginPath());
        $this->getService($request)->displayHeader($title, $breadcrumbs, array());
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
