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
    function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        // Do not load the plugin if tracker is not installed & active
        if (defined('TRACKER_BASE_URL')) {
            $this->_addHook('cssfile', 'cssfile', false);
            $this->_addHook(Event::COMBINED_SCRIPTS, 'combined_scripts', false);
        }
    }
    
    /**
     * @return AgileDashboardPluginInfo
     */
    function getPluginInfo() {
        if (!$this->pluginInfo) {
            include_once 'AgileDashboardPluginInfo.class.php';
            $this->pluginInfo = new AgileDashboardPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function cssfile($params) {
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
        switch($request->get('action')) {
            case 'show':
                $view_builder = new Tracker_CrossSearch_ViewBuilder(Tracker_FormElementFactory::instance(), TrackerFactory::instance());
                $this->renderAction('show', $request, array($view_builder));
                break;
            case 'new':
                $this->renderAction('new_', $request);
                break;
            case 'create':
                $this->executeAction('create', $request);
                break;
            case 'index':
            default:
                $this->renderAction('index', $request);
        }
    }
    
    private $header_title = array(
        'index' => 'Plannings',
        'new_'  => 'New Planning',
        'show'  => 'Release planning'
    );
    
    private function getService($request) {
        if ($this->service == null) {
            $project = ProjectManager::instance()->getProject($request->get('group_id'));
            $this->service = $project->getService('plugin_agiledashboard');
        }
        return $this->service;
    }
    
    private function displayHeader($request, $title) {
        $breadcrumbs = array(
            array('url'   => 'bla',
                  'title' => "Product A"),
            array('url'   => 'bla',
                  'title' => "Release 4.0.27"),
        );
        
        $this->getService($request)->displayHeader($title, $breadcrumbs, array());
    }
    
    private function displayFooter($request) {
        $this->getService($request)->displayFooter();
    }
    
    private function buildController($request) {
        require_once 'Planning/Controller.class.php';
        require_once 'Planning/PlanningFactory.class.php';
        
        return new Planning_Controller($request,
                                       Tracker_ArtifactFactory::instance(),
                                       new PlanningFactory(new PlanningDao()),
                                       TrackerFactory::instance());
    }
    
    private function renderAction($action_name, Codendi_Request $request, array $args = array()) {
        $this->displayHeader($request, $this->header_title[$action_name]);
        call_user_func_array(array($this, 'executeAction'), array($action_name, $request, $args));
        $this->displayFooter($request);
    }
    
    private function executeAction($action_name, Codendi_Request $request, array $args = array()) {
        $controller = $this->buildController($request);
        call_user_func_array(array($controller, $action_name), $args);
    }
    
}

?>
