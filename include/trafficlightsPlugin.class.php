<?php
/**
 * Copyright (c) Enalean, 2014-2015. All Rights Reserved.
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

require_once 'constants.php';

use \Tuleap\Trafficlights\Dao;
use \Tuleap\Trafficlights\Config;
use \Tuleap\Trafficlights\FirstConfigCreator;

class TrafficlightsPlugin extends Plugin {

    /**
     * Plugin constructor
     */
    function __construct($id) {
        parent::__construct($id);
        $this->filesystem_path = TRAFFICLIGHTS_BASE_DIR;
        $this->setScope(self::SCOPE_PROJECT);
        $this->addHook('cssfile');
        $this->addHook('javascript_file');
        $this->addHook(Event::REST_PROJECT_RESOURCES);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICE_ICON);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(TRACKER_EVENT_COMPLEMENT_REFERENCE_INFORMATION);
        $this->addHook(TRACKER_EVENT_PROJECT_CREATION_TRACKERS_REQUIRED);
        $this->addHook(TRACKER_EVENT_TRACKERS_DUPLICATED);
    }

    public function getServiceShortname() {
        return 'plugin_trafficlights';
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e813';
    }

    public function service_classnames($params) {
        $params['classnames'][$this->getServiceShortname()] = 'Trafficlights\\Service';
    }

    public function tracker_event_complement_reference_information(array $params) {
        $tracker = $params['artifact']->getTracker();
        $project = $tracker->getProject();

        $plugin_trafficlights_is_used = $project->usesService($this->getServiceShortname());
        if ($plugin_trafficlights_is_used) {
            $reference_information = array(
                'title' => $GLOBALS['Language']->getText('plugin_trafficlights', 'references_graph_title'),
                'links' => array()
            );

            $link = array(
                'icon' => $this->getPluginPath() . '/themes/default/images/artifact-link-graph.svg',
                'link' => $this->getPluginPath() . '/?group_id=' . $tracker->getGroupId() . '#/graph/' . $params['artifact']->getId(),
                'label'=> $GLOBALS['Language']->getText('plugin_trafficlights', 'references_graph_url')
            );

            $reference_information['links'][] = $link;
            $params['reference_information'][] = $reference_information;
        }
    }

    /**
     * List TrafficLights trackers to duplicate
     *
     * @param array $params The project duplication parameters (source project id, tracker ids list)
     *
     */
    public function tracker_event_project_creation_trackers_required(array $params)
    {
        $config = new Config(new Dao());
        $project = ProjectManager::instance()->getProject($params['project_id']);

        $plugin_trafficlights_is_used = $project->usesService($this->getServiceShortname());
        if (! $plugin_trafficlights_is_used) {
            return;
        }

        $params['tracker_ids_list'] = array_merge(
            $params['tracker_ids_list'],
            array(
                $config->getCampaignTrackerId($project),
                $config->getTestDefinitionTrackerId($project),
                $config->getTestExecutionTrackerId($project)
            )
        );
    }

    /**
     * Configure new project's TrafficLights trackers
     *
     * @param mixed array $params The duplication params (tracker_mapping array, field_mapping array)
     *
     */
    public function tracker_event_trackers_duplicated(array $params)
    {
        $config = new Config(new Dao());
        $from_project = ProjectManager::instance()->getProject($params['source_project_id']);
        $to_project = ProjectManager::instance()->getProject($params['group_id']);

        $plugin_trafficlights_is_used = $to_project->usesService($this->getServiceShortname());
        if (! $plugin_trafficlights_is_used) {
            return;
        }

        $config_creator = new FirstConfigCreator($config);
        $config_creator->createConfigForProjectFromTemplate($to_project, $from_project, $params['tracker_mapping']);
    }

    /**
     * @return TrafficlightsPluginInfo
     */
    function getPluginInfo() {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new TrafficlightsPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function cssfile($params) {
        // Only show the stylesheet if we're actually in the Trafficlights pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getPluginPath().'/scripts/angular/bin/assets/trafficlights.css" />';
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    public function javascript_file() {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="scripts/move-breadcrumb.js"></script>'."\n";
            echo '<script type="text/javascript" src="scripts/resize-content.js"></script>'."\n";
        }
    }

    function process(Codendi_Request $request) {
        $config = new \Tuleap\Trafficlights\Config(new \Tuleap\Trafficlights\Dao());
        $router = new Tuleap\Trafficlights\Router($this, $config);
        $router->route($request);
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources(array $params) {
        $injector = new Trafficlights_REST_ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @see REST_PROJECT_RESOURCES
     */
    function rest_project_resources(array $params) {
        $injector = new Trafficlights_REST_ResourcesInjector();
        $injector->declareProjectResource($params['resources'], $params['project']);
    }
}
