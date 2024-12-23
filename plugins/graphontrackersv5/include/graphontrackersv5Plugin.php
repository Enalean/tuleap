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

declare(strict_types=1);

use Tuleap\GraphOnTrackersV5\Async\ChartDataController;
use Tuleap\GraphOnTrackersV5\DataAccess\GraphOnTrackersV5_Chart_Bar;
use Tuleap\GraphOnTrackersV5\DataAccess\GraphOnTrackersV5_Chart_Burndown;
use Tuleap\GraphOnTrackersV5\DataAccess\GraphOnTrackersV5_Chart_CumulativeFlow;
use Tuleap\GraphOnTrackersV5\DataAccess\GraphOnTrackersV5_Chart_Gantt;
use Tuleap\GraphOnTrackersV5\DataAccess\GraphOnTrackersV5_Chart_Pie;
use Tuleap\GraphOnTrackersV5\DataAccess\GraphOnTrackersV5_ChartFactory;
use Tuleap\GraphOnTrackersV5\GraphOnTrackersV5_Renderer;
use Tuleap\GraphOnTrackersV5\GraphOnTrackersV5_Widget_MyChart;
use Tuleap\GraphOnTrackersV5\GraphOnTrackersV5_Widget_ProjectChart;
use Tuleap\GraphOnTrackersV5\XML\Template\CompleteIssuesTemplate;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Project\Registration\Template\IssuesTemplateDashboardDefinition;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Tracker\Report\Renderer\ImportRendererFromXmlEvent;
use Tuleap\Tracker\Semantic\Timeframe\Events\GetSemanticTimeframeUsageEvent;
use Tuleap\Tracker\Template\CompleteIssuesTemplateEvent;

require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';

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
            //Tracker report renderer
            $this->addHook('tracker_report_renderer_instance', 'tracker_report_renderer_instance');
            $this->addHook(ImportRendererFromXmlEvent::NAME);
            $this->addHook('tracker_report_add_renderer', 'tracker_report_add_renderer');
            $this->addHook('tracker_report_create_renderer', 'tracker_report_create_renderer');
            $this->addHook('tracker_report_renderer_types', 'tracker_report_renderer_types');
            $this->addHook('trackers_get_renderers', 'trackers_get_renderers');

            //Widgets
            $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
            $this->addHook(\Tuleap\Widget\Event\GetUserWidgetList::NAME);
            $this->addHook(\Tuleap\Widget\Event\GetProjectWidgetList::NAME);

            $this->addHook('graphontrackersv5_load_chart_factories', 'graphontrackersv5_load_chart_factories');

            $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);
            $this->addHook(CompleteIssuesTemplateEvent::NAME);
            $this->addHook(IssuesTemplateDashboardDefinition::NAME);
            $this->addHook(GetSemanticTimeframeUsageEvent::NAME);
        }
        $this->allowedForProject = [];
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return ['tracker'];
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
            if (isset($params['row']['charts'], $params['row']['charts']->chart, $params['row']['mapping'])) {
                $charts = [];
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
        $params['types'][self::RENDERER_TYPE] = dgettext('tuleap-tracker', 'Chart(s)');
    }

    /**
     * This hook adds a  GraphOnTrackersV5_Renderer in a renderers array
     *
     * @param array types Output parameter. Expected format: $types['my_type'] => 'Label of the type'
     */
    public function trackers_get_renderers($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['renderer_type'] == 'plugin_graphontrackersv5') {
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
                $get_widget_event->setWidget(new GraphOnTrackersV5_Widget_MyChart());
                break;
            case 'project_plugin_graphontrackersv5_chart':
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
        $this->removeOrphanWidgets(['my_plugin_graphontrackersv5_chart', 'project_plugin_graphontrackersv5_chart']);
    }

    /**
     * function to get plugin info
     */
    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(new PluginDescriptor(
                dgettext('tuleap-graphontrackersv5', 'Graphs On Trackers v5'),
                dgettext('tuleap-graphontrackersv5', 'Plugin that allow drawing graphic on trackers v5'),
            ));
            $this->pluginInfo = $plugin_info;
        }
        return $this->pluginInfo;
    }

    /**
     * Return true if current project has the right to use this plugin.
     */
    public function isAllowed($group_id): bool
    {
        $request  = HTTPRequest::instance();
        $group_id = (int) $request->get('group_id');
        if (! isset($this->allowedForProject[$group_id])) {
            $pM                                 = PluginManager::instance();
            $this->allowedForProject[$group_id] = $pM->isPluginAllowedForProject($this, $group_id);
        }
        return $this->allowedForProject[$group_id];
    }

    private function canIncludeStylesheets()
    {
        return strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL . '/') === 0;
    }

    public function graphontrackersv5_load_chart_factories($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['factories']['pie']             = [
            'chart_type'      => 'pie',
            'chart_classname' => GraphOnTrackersV5_Chart_Pie::class,
            'title'           => dgettext('tuleap-graphontrackersv5', 'Pie'),
        ];
        $params['factories']['bar']             = [
            'chart_type'      => 'bar',
            'chart_classname' => GraphOnTrackersV5_Chart_Bar::class,
            'title'           => dgettext('tuleap-graphontrackersv5', 'Bar'),
        ];
        $params['factories']['gantt']           = [
            'chart_type'      => 'gantt',
            'chart_classname' => GraphOnTrackersV5_Chart_Gantt::class,
            'title'           => dgettext('tuleap-graphontrackersv5', 'Gantt'),
        ];
        $params['factories']['burndown']        = [
            //The type of the chart
            'chart_type'      => 'burndown',
            //The classname of the chart. The class must be already declared.
            'chart_classname' => GraphOnTrackersV5_Chart_Burndown::class,
            //The title for the button 'Add a chart'
            'title'           => dgettext('tuleap-graphontrackersv5', 'Scrum BurnDown'),
        ];
        $params['factories']['cumulative_flow'] = [
            //The type of the chart
            'chart_type'      => 'cumulative_flow',
            //The classname of the chart. The class must be already declared.
            'chart_classname' => GraphOnTrackersV5_Chart_CumulativeFlow::class,
            //The title for the button 'Add a chart'
            'title'           => dgettext('tuleap-graphontrackersv5', 'Cumulative flow chart'),
        ];
    }

    public function getAssets(): IncludeViteAssets
    {
        return new IncludeViteAssets(
            __DIR__ . '/../scripts/graph-loader/frontend-assets',
            '/assets/graphontrackersv5/graph-loader',
        );
    }

    public function routeGetChart(): ChartDataController
    {
        return new ChartDataController(
            Tracker_ReportFactory::instance(),
            Tracker_Report_RendererFactory::instance(),
            GraphOnTrackersV5_ChartFactory::instance(),
            UserManager::instance(),
            new \Tuleap\Http\Response\JSONResponseBuilder(\Tuleap\Http\HTTPFactoryBuilder::responseFactory(), \Tuleap\Http\HTTPFactoryBuilder::streamFactory()),
            new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter(),
            new \Tuleap\Http\Server\SessionWriteCloseMiddleware()
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->get(
            '/plugins/graphontrackersv5/report/{report_id:\d+}/renderer/{renderer_id:-?\d+}/chart/{chart_id:-?\d+}',
            $this->getRouteHandler('routeGetChart')
        );
    }

    public function completeIssuesTemplate(CompleteIssuesTemplateEvent $event): void
    {
        $event->addAllIssuesRenderers(...CompleteIssuesTemplate::getAllIssuesRenderers());
        $event->addMyIssuesRenderers(CompleteIssuesTemplate::getMyIssuesRenderer());
        $event->addOpenIssuesRenderers(CompleteIssuesTemplate::getOpenIssuesRenderer());
    }

    public function issuesTemplateDashboardDefinition(IssuesTemplateDashboardDefinition $dashboard_definition): void
    {
        CompleteIssuesTemplate::defineDashboards($dashboard_definition);
    }

    public function getSemanticTimeframeUsageEvent(GetSemanticTimeframeUsageEvent $event): void
    {
        $event->addUsageLocation(
            dgettext('tuleap-graphontrackersv5', 'graph on tracker')
        );
    }
}
