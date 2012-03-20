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
        switch($request->get('func')) {
            case 'show':
                $this->displayPlanning($request);
                break;
            case 'create':
                $this->displayCreate($request);
                break;
            case 'doCreate':
                $this->doCreate($request);
                break;
            case 'index':
            default:
                $this->displayIndex($request);
        }
    }
    
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
    
    private function displayIndex(Codendi_Request $request) {
        $controller = $this->buildController($request);
        
        $this->displayHeader($request, "Plannings");
        $controller->index();
        $this->displayFooter($request);
    }
    
    private function displayCreate(Codendi_Request $request) {
        $controller = $this->buildController($request);
        
        $this->displayHeader($request, "New Planning");
        $controller->create();
        $this->displayFooter($request);
    }
    
    private function doCreate(Codendi_Request $request) {
        $controller = $this->buildController($request);
        $controller->doCreate();
    }

    private function displayPlanning(Codendi_Request $request) {
        $controller = $this->buildController($request);
        
        $this->displayHeader($request, "Release planning");
        $controller->display();
        $this->displayFooter($request);
    }
    
}
class ReleaseViewPresenter {

}

?>
