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
require_once dirname(__FILE__). '/../../tracker/include/MustacheRenderer.class.php';
/**
 * AgileDashboardPlugin
 */
class AgileDashboardPlugin extends Plugin {

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
        $this->displayPlanning($request);
    }

    public function displayPlanning(Codendi_Request $request) {
        $mustacheRenderer = new MustacheRenderer(dirname(__FILE__).'/../templates');
        $presenter = new ReleaseViewPresenter();
        
        $breadcrumbs = array(
            array('url'   => 'bla',
                  'title' => "Product A"),
            array('url'   => 'bla',
                  'title' => "Release 4.0.27"),
        );
        
        
        $project = ProjectManager::instance()->getProject($request->get('group_id'));
        $service = $project->getService('plugin_agiledashboard');
        $service->displayHeader("Release planning", $breadcrumbs, array());
        
        
        
        $mustacheRenderer->render("release-view", $presenter);
        $service->displayFooter();
    }
    
}
class ReleaseViewPresenter {

}

?>
