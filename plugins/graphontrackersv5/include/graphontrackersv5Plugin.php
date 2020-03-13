<?php
/**
 * Copyright (c) Enalean, 2014 -Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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

use Tuleap\GraphOnTrackersV5\Async\ChartDataController;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Tracker\Report\Renderer\ImportRendererFromXmlEvent;

require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ .  '/../vendor/autoload.php';

class GraphOnTrackersV5Plugin extends Plugin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const RENDERER_TYPE = 'plugin_graphontrackersv5';

    public $report_id;
    public $chunksz;
    public $offset;
    public $advsrch;
    public $morder;
    public $prefs;
    public $group_id;
    public $atid;
    public $set;
    public $report_graphic_id;
    public $allowedForProject;

    /**
     *
     *
     * @param int $id plugin id
     */
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);

        bindTextDomain('tuleap-graphontrackersv5', __DIR__ . '/../site-content');

        // Do not load the plugin if tracker is not installed & active
        if (defined('TRACKER_BASE_URL')) {
            $this->addHook('cssfile', 'cssFile', false);

            //Tracker report renderer
            $this->addHook('tracker_report_renderer_instance', 'tracker_report_renderer_instance', false);
            $this->addHook(ImportRendererFromXmlEvent::NAME);
            $this->addHook('tracker_report_add_renderer', 'tracker_report_add_renderer', false);
            $this->addHook('tracker_report_create_renderer', 'tracker_report_create_renderer', false);
            $this->addHook('tracker_report_renderer_types', 'tracker_report_renderer_types', false);
            $this->addHook('trackers_get_renderers', 'trackers_get_renderers', false);

            //Widgets
            $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
            $this->addHook(\Tuleap\Widget\Event\GetUserWidgetList::NAME);
            $this->addHook(\Tuleap\Widget\Event\GetProjectWidgetList::NAME);

            $this->addHook('graphontrackersv5_load_chart_factories', 'graphontrackersv5_load_chart_factories', false);

            $this->addHook('javascript_file');
            $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);
        }
        $this->allowedForProject = array();
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return array('tracker');
    }


    /**
     * This hook ask to create a new instance of a renderer
     *
     * @param mixed instance Output parameter. must contain the new instance
     * @param string type the type of the new renderer
     * @param array row the base properties identifying the renderer (id, name, description, rank)
     * @param Report report the report
     *
     * @return void
     */
    public function tracker_report_renderer_instance($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['type'] == self::RENDERER_TYPE) {
            require_once('GraphOnTrackersV5_Renderer.class.php');
            $params['instance'] = new GraphOnTrackersV5_Renderer(
                $params['row']['id'],
                $params['report'],
                $params['row']['name'],
                $params['row']['description'],
                $params['row']['rank'],
                $this,
                UserManager::instance(),
                Tracker_FormElementFactory::instance()
            );
            if ($params['store_in_session']) {
                $params['instance']->initiateSession();
            }
            $f = GraphOnTrackersV5_ChartFactory::instance();
            if (isset($params['row']['charts']) && isset($params['row']['mapping'])) {
                $charts = array();
                foreach ($params['row']['charts']->chart as $chart) {
                    $charts[] = $f->getInstanceFromXML($chart, $params['instance'], $params['row']['mapping'], $params['store_in_session']);
                }
            } else {
                $charts = $f->getCharts($params['instance'], $params['store_in_session']);
            }
            $params['instance']->setCharts($charts);
        }
    }

    /**
     * This hook ask to create a new instance of a renderer from XML
     */
    public function importRendererFromXmlEvent(ImportRendererFromXmlEvent $event)
    {
        if ($event->getType() === self::RENDERER_TYPE) {
            $event->setRowKey('charts', $event->getXml()->charts);
            $event->setRowKey('mapping', $event->getXmlMapping());
        }
    }


    /**
     * This hook says that a new renderer has been added to a report session
     * Maybe it is time to set default specific parameters of the renderer?
     *
     * @param int renderer_id the id of the new renderer
     * @param string type the type of the new renderer
     * @param Report report the report
     */
    public function tracker_report_add_renderer($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['type'] == self::RENDERER_TYPE) {
            //Nothing to do for now
        }
    }

    /**
     * This hook says that a new renderer has been added to a report and therefore must be created into db
     * Maybe it is time to set default specific parameters of the renderer?
     *
     * @param int renderer_id the id of the new renderer
     * @param string type the type of the new renderer
     * @param Report report the report
     */
    public function tracker_report_create_renderer($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['type'] == self::RENDERER_TYPE) {
            //Nothing to do for now
        }
    }

    /**
     * This hook ask for types of report renderer provided by the listener
     *
     * @param array types Output parameter. Expected format: $types['my_type'] => 'Label of the type'
     */
    public function tracker_report_renderer_types($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['types'][self::RENDERER_TYPE] = $GLOBALS['Language']->getText('plugin_tracker_report', 'charts');
    }

     /**
     * This hook adds a  GraphOnTrackersV5_Renderer in a renderers array
     *
     * @param array types Output parameter. Expected format: $types['my_type'] => 'Label of the type'
     */
    public function trackers_get_renderers($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['renderer_type'] == 'plugin_graphontrackersv5') {
            require_once('GraphOnTrackersV5_Renderer.class.php');
            $params['renderers'][$params['renderer_key']] = new GraphOnTrackersV5_Renderer(
                $params['renderer_key'],
                $params['report'],
                $params['name'],
                $params['description'],
                $params['rank'],
                $this,
                UserManager::instance(),
                Tracker_FormElementFactory::instance()
            );
            $params['renderers'][$params['renderer_key']]->initiateSession();
        }
    }


    /**
     * Search for an instance of a specific widget
     *
     */
    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_widget_event)
    {
        switch ($get_widget_event->getName()) {
            case 'my_plugin_graphontrackersv5_chart':
                require_once('GraphOnTrackersV5_Widget_MyChart.class.php');
                $get_widget_event->setWidget(new GraphOnTrackersV5_Widget_MyChart());
                break;
            case 'project_plugin_graphontrackersv5_chart':
                require_once('GraphOnTrackersV5_Widget_ProjectChart.class.php');
                $get_widget_event->setWidget(new GraphOnTrackersV5_Widget_ProjectChart());
                break;
            default:
                break;
        }
    }

    public function getUserWidgetList(\Tuleap\Widget\Event\GetUserWidgetList $event)
    {
        $event->addWidget('my_plugin_graphontrackersv5_chart');
    }

    public function getProjectWidgetList(\Tuleap\Widget\Event\GetProjectWidgetList $event)
    {
        $event->addWidget('project_plugin_graphontrackersv5_chart');
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets(array('my_plugin_graphontrackersv5_chart', 'project_plugin_graphontrackersv5_chart'));
    }


    /**
     * function to get plugin info
     */
    public function getPluginInfo()
    {
        if (!is_a($this->pluginInfo, 'GraphOnTrackersV5PluginInfo')) {
            require_once('GraphOnTrackersV5PluginInfo.class.php');
            $this->pluginInfo = new GraphOnTrackersV5PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * Return true if current project has the right to use this plugin.
     */
    public function isAllowed($group_id)
    {
        $request = HTTPRequest::instance();
        $group_id = (int) $request->get('group_id');
        if (!isset($this->allowedForProject[$group_id])) {
            $pM = PluginManager::instance();
            $this->allowedForProject[$group_id] = $pM->isPluginAllowedForProject($this, $group_id);
        }
        return $this->allowedForProject[$group_id];
    }

    public function cssFile(): void
    {
        if ($this->canIncludeStylesheets()) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getAssets()->getFileURL('style.css') . '" />';
        }
    }

    private function canIncludeStylesheets()
    {
        return strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL . '/') === 0;
    }

    public function graphontrackersv5_load_chart_factories($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        require_once('GraphOnTrackersV5_Renderer.class.php');
        require_once('data-access/GraphOnTrackersV5_Chart_Bar.class.php');
        require_once('data-access/GraphOnTrackersV5_Chart_Pie.class.php');
        require_once('data-access/GraphOnTrackersV5_Chart_Gantt.class.php');
        require_once('data-access/GraphOnTrackersV5_Chart_Burndown.class.php');
        require_once('data-access/GraphOnTrackersV5_Chart_CumulativeFlow.class.php');
        $params['factories']['pie'] = array(
            'chart_type'      => 'pie',
            'chart_classname' => 'GraphOnTrackersV5_Chart_Pie',
            'title'           => $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report', 'pie'),
        );
        $params['factories']['bar'] = array(
            'chart_type'      => 'bar',
            'chart_classname' => 'GraphOnTrackersV5_Chart_Bar',
            'title'           => $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report', 'bar'),
        );
        $params['factories']['gantt'] = array(
            'chart_type'      => 'gantt',
            'chart_classname' => 'GraphOnTrackersV5_Chart_Gantt',
            'title'           => $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report', 'gantt'),
        );
        $params['factories']['burndown'] = array(
            //The type of the chart
            'chart_type'      => 'burndown',
            //The classname of the chart. The class must be already declared.
            'chart_classname' => 'GraphOnTrackersV5_Chart_Burndown',
            //The title for the button 'Add a chart'
            'title'           => $GLOBALS['Language']->getText('plugin_graphontrackersv5_scrum', 'add_title_burndown'),
        );
        $params['factories']['cumulative_flow'] = array(
            //The type of the chart
            'chart_type'      => 'cumulative_flow',
            //The classname of the chart. The class must be already declared.
            'chart_classname' => 'GraphOnTrackersV5_Chart_CumulativeFlow',
            //The title for the button 'Add a chart'
            'title'           => $GLOBALS['Language']->getText('plugin_graphontrackersv5_include_report', 'cumulative_flow'),
        );
    }

    /**
     * @see javascript_file
     */
    public function javascript_file()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $tracker_plugin = PluginManager::instance()->getPluginByName('tracker');
        if ($tracker_plugin->currentRequestIsForPlugin()) {
            echo $this->getAssets()->getHTMLSnippet('graphontrackersv5.js');
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/graphontrackersv5',
            '/assets/graphontrackersv5'
        );
    }

    public function routeGetChart(): ChartDataController
    {
        return new ChartDataController(
            Tracker_ReportFactory::instance(),
            Tracker_Report_RendererFactory::instance(),
            GraphOnTrackersV5_ChartFactory::instance()
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->get(
            "/plugins/graphontrackersv5/report/{report_id:\d+}/renderer/{renderer_id:-?\d+}/chart/{chart_id:-?\d+}",
            $this->getRouteHandler('routeGetChart')
        );
    }
}
