<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Dashboard\User\UserDashboardDao;
use Tuleap\Dashboard\User\UserDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\GraphOnTrackersV5\Chart\D3CompatibleChartVisitor;
use Tuleap\GraphOnTrackersV5\Chart\Visitable;
use Tuleap\Tracker\Report\WidgetAdditionalButtonPresenter;
use Tuleap\Widget\WidgetFactory;

/**
 * Describe a chart
 *
 * This class must be overriden to provide your own concrete chart (Pie, Bar, ..)
 */
abstract class GraphOnTrackersV5_Chart implements Visitable
{
    public const MARKER_BEGINNING_OUTPUT_FETCH = '💩';

    public $id;
    protected $rank;
    protected $title;
    protected $description;
    protected $width;
    protected $height;

    private $engine = null;

    /** @var GraphOnTrackersV5_Renderer */
    public $renderer;
    private $mustache_renderer;

    /**
     * @param Renderer The renderer wich contains the chart
     * @param int The id of the chart
     * @param int The rank of the chart
     * @param string The title of the chart
     * @param string The description of the chart
     * @param int The width of the chart
     * @param int The height of the chart
     */
    public function __construct($renderer, $id, $rank, $title, $description, $width, $height)
    {
        $this->renderer          = $renderer;
        $this->id                = $id;
        $this->rank              = $rank;
        $this->title             = $title;
        $this->description       = $description;
        $this->width             = $width;
        $this->height            = $height;
        $this->mustache_renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates');
    }

    public function registerInSession()
    {
        $this->report_session = self::getSession($this->renderer->report->id, $this->renderer->id);
        $this->report_session->set("$this->id.id", $this->id);
        $this->report_session->set("$this->id.rank", $this->rank);
        $this->report_session->set("$this->id.title", $this->title);
        $this->report_session->set("$this->id.description", $this->description);
        $this->report_session->set("$this->id.width", $this->width);
        $this->report_session->set("$this->id.height", $this->height);
        $this->report_session->set("$this->id.report_graphic_id", $this->renderer->id);
    }

    abstract public function loadFromSession();
    abstract public function loadFromDb();

    /**
     *
     * @param int $report_id
     * @param int $renderer_id
     * @param int $chart_id
     *
     * @return Tracker_Report_Session
     */
    public static function getSession($report_id, $renderer_id)
    {
        $session = new Tracker_Report_Session($report_id);
        $session->changeSessionNamespace("renderers.{$renderer_id}.charts");
        return $session;
    }

    /* Getters and setters */
    public function getId()
    {
        return $this->id;
    }
    public function getRank()
    {
        return $this->rank;
    }
    public function setRank($rank)
    {
        $this->rank = $rank;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function setTitle($title)
    {
        $this->title = $title;
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function setDescription($description)
    {
        $this->description = $description;
    }
    public function getRenderer()
    {
        return $this->renderer;
    }
    public function setRenderer($renderer)
    {
        $this->renderer = $renderer;
    }
    public function getHeight()
    {
        return $this->height;
    }
    public function setHeight($height)
    {
        return $this->height = $height;
    }
    public function getWidth()
    {
        return $this->width;
    }
    public function setWidth($width)
    {
        return $this->width = $width;
    }
    public static function getDefaultHeight()
    {
        return 400;
    }
    public static function getDefaultWidth()
    {
        return 600;
    }
    /**
     * Display the html <img /> tag to embed the chart in a html page.
     */
    public function fetchImgTag($store_in_session = true)
    {
        $html = '';

        $urlimg = $this->getStrokeUrl($store_in_session);

        $html .= '<img  src="' . $urlimg . '"  ismap usemap="#map' . $this->getId() . '"  ';
        if ($this->width) {
            $html .= ' width="' . $this->width . '" ';
        }
        if ($this->height) {
            $html .= ' height="' . $this->height . '" ';
        }
        $html .= ' alt="' . $this->title . '" border="0">';
        return $html;
    }

    public function getStrokeUrl($store_in_session = true)
    {
        return TRACKER_BASE_URL . '/?' . http_build_query(array(
                     '_jpg_csimd' => '1',
                     'report'     => $this->renderer->report->id,
                     'renderer'   => $this->renderer->id,
                     'func'       => 'renderer',
                     'store_in_session' => $store_in_session,
                     'renderer_plugin_graphontrackersv5[stroke]' => $this->getId()));
    }

    /**
     * Display both <img /> and <map /> tags to embed the chart in a html page
     */
    public function display()
    {
        echo $this->fetch();
    }

    public function fetch($store_in_session = true)
    {
        $html = '';
        if ($this->userCanVisualize()) {
            $e = $this->buildGraph();
            if ($e) {
                $html  = $this->getHTMLImageMapWithoutInterruptingExecutionFlow($e, 'map' . $this->getId());
                $html .= $this->fetchImgTag($store_in_session);
            }
        }
        return $html;
    }

    /**
     * @return string|false
     */
    private function getHTMLImageMapWithoutInterruptingExecutionFlow(GraphOnTrackersV5_Engine $engine, $image_map)
    {
        ob_start();
        echo self::MARKER_BEGINNING_OUTPUT_FETCH;
        try {
            $html = $engine->graph->GetHTMLImageMap($image_map);
        } catch (Exception $ex) {
            ob_clean();
            throw $ex;
        }
        echo mb_substr(ob_get_clean(), mb_strlen(self::MARKER_BEGINNING_OUTPUT_FETCH));
        return $html;
    }

    private function fetchGraphAnchor($content)
    {
        $not_d3_flag = $this->isGraphDrawnByD3() ? 'false' : 'true';
        $renderer_id = $this->renderer->getId();
        $report_id   = $this->renderer->report->getId();
        return '<div class="tracker_report_renderer_graphontrackers_graph plugin_graphontrackersv5_chart"
                     data-graph-id="' . $this->getId() . '"
                     data-renderer-id="' . $renderer_id . '"
                     data-report-id="' . $report_id . '"
                     data-does-not-use-d3="' . $not_d3_flag . '"
                >' . $content . '</div>';
    }

    private function fetchAdditionnalButton()
    {
        $is_a_table_renderer = false;

        $html = $this->getTemplateRenderer()->renderToString(
            'widget-additionnal-button',
            new WidgetAdditionalButtonPresenter($this->getTracker(), HTTPRequest::instance(), $is_a_table_renderer)
        );

        return $html;
    }

    private function getTemplateRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR . '/report');
    }

    public function fetchOnReport(GraphOnTrackersV5_Renderer $renderer, PFUser $current_user, $read_only, $store_in_session = true)
    {
        if ($this->isGraphDrawnByD3()) {
            $content   = '';
            $classname = 'd3graph';
        } else {
            $content   = $this->fetch($store_in_session);
            $classname = '';
        }
        $hp = Codendi_HTMLPurifier::instance();

        $html  = '';
        $html .= '<div class="widget ' . $classname . '">';
        $html .= '<div class="widget_titlebar" title="' . $hp->purify($this->getDescription()) . '">';
        $html .= '<div class="widget_titlebar_title">' . $hp->purify($this->getTitle()) . '</div>';
        $html .= '<div class="plugin_graphontrackersv5_widget_actions">';
        $html .= $this->fetchActionButtons($renderer, $current_user, $read_only);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="widget_content">';
        $html .= $this->fetchGraphAnchor($content);
        $html .= '</div>'; // content
        $html .= '</div>'; // widget

        return $html;
    }

    protected function fetchActionButtons(GraphOnTrackersV5_Renderer $renderer, PFUser $current_user, $readonly)
    {
        $add_to_dashboard_params      = array(
            'action' => 'add-widget',
            'chart'  => array(
                'title'    => $this->getTitle(),
                'chart_id' => $this->getId(),
            )

        );

        $url = '?' . http_build_query(array(
            'report'   => $renderer->report->id,
            'renderer' => $renderer->id,
            'func'     => 'renderer',
        ));

        $csrf             = new CSRFSynchronizerToken('/my/');
        $my_dashboard_url = '/widgets/?' .
            http_build_query(
                array_merge(
                    array(
                        'dashboard-type'      => UserDashboardController::DASHBOARD_TYPE,
                        'widget-name'         => 'my_plugin_graphontrackersv5_chart',
                        $csrf->getTokenName() => $csrf->getToken()
                    ),
                    $add_to_dashboard_params
                )
            );

        $project_dashboard_url = '';
        $project = $renderer->report->getTracker()->getProject();
        if ($project->userIsAdmin($current_user)) {
            $csrf                  = new CSRFSynchronizerToken('/project/');
            $project_dashboard_url = '/widgets/?' .
                http_build_query(
                    array_merge(
                        array(
                            'widget-name'         => 'project_plugin_graphontrackersv5_chart',
                            'dashboard-type'      => ProjectDashboardController::DASHBOARD_TYPE,
                            $csrf->getTokenName() => $csrf->getToken(),
                            'group_id'            => $project->getID()

                        ),
                        $add_to_dashboard_params
                    )
                );
        }

        $delete_chart_url = $url . '&renderer_plugin_graphontrackersv5[delete_chart][' . $this->getId() . ']';
        $edit_chart_url   = $url . '&renderer_plugin_graphontrackersv5[edit_chart]=' . $this->getId();

        $my_dashboards_presenters    = $this->getAvailableDashboardsForUser($current_user);
        $project_dashboard_presenter = $this->getAvailableDashboardsForProject($project);

        return $this->mustache_renderer->renderToString(
            'graph-actions',
            new GraphOnTrackersV5_GraphActionsPresenter(
                $this,
                $this->graphCanBeUpdated($readonly, $current_user),
                $my_dashboard_url,
                $project_dashboard_url,
                $delete_chart_url,
                $edit_chart_url,
                $my_dashboards_presenters,
                $project_dashboard_presenter
            )
        );
    }

    private function graphCanBeUpdated($readonly, PFUser $current_user)
    {
        return !$readonly && ! $current_user->isAnonymous();
    }

    /**
     * Fetch chart data as an array
     */
    public function fetchAsArray()
    {
        $engine = $this->getEngineWithData();
        if (! $this->userCanVisualize() || ! $engine) {
            return array();
        }

        return $engine->toArray();
    }

    public function getRow()
    {
        return array_merge(array(
            'id'          => $this->getId(),
            'rank'        => $this->getRank(),
            'title'       => $this->getTitle(),
            'description' => $this->getDescription(),
            'width'       => $this->getWidth(),
            'height'      => $this->getHeight(),
        ), $this->getSpecificRow());
    }

    /**
     * Stroke the chart.
     * Build the image and send it to the client
     */
    public function stroke()
    {
        $e = $this->buildGraph();
        if ($e && is_object($e->graph)) {
            $e->graph->StrokeCSIM();
        }
    }

    /**
     * Prepare the building of the graph
     * @return GraphOnTracker_Chart_Engine
     */
    protected function buildGraph()
    {
        $e = $this->getEngineWithData();
        if ($e) {
            //build the chart
            $e->buildGraph();

            return $e;
        } else {
            return false;
        }
    }

    /**
     * @return GraphOnTrackersV5_Engine|false
     */
    protected function getEngineWithData()
    {
        if (! $this->engine) {
            //Get the chart engine
            $this->engine = $this->getEngine();
        }

        //Define the artifacts which must be added to the chart
        $artifacts = $this->renderer->report->getMatchingIds();

        //Get the ChartDataBuilder for this chart
        $pcdb = $this->getChartDataBuilder($artifacts);

        //prepare the propeties for the chart
        $pcdb->buildProperties($this->engine);

        if (! $this->engine->validData()) {
            $this->engine = false;
        }

        return $this->engine;
    }

    protected function getTracker()
    {
        return TrackerFactory::instance()->getTrackerById($this->renderer->report->tracker_id);
    }

    /**
     * Get the properties of the chart as a HTML_Element array.
     *
     * Default properties are id, title, description, rank and dimensions
     *
     * Feel free to override this method to provide your own properties
     * @return array
     */
    public function getProperties()
    {
        $siblings = $this->getSiblingsForRankSelectbox();

        return array(
            'id'          => new HTML_Element_Input_Hidden($GLOBALS['Language']->getText('plugin_graphontrackersv5_property', 'id'), 'chart[id]', $this->getId()),
            'title'       => new HTML_Element_Input_Text($GLOBALS['Language']->getText('plugin_graphontrackersv5_property', 'title'), 'chart[title]', $this->getTitle()),
            'description' => new HTML_Element_Textarea($GLOBALS['Language']->getText('plugin_graphontrackersv5_property', 'description'), 'chart[description]', $this->getDescription()),
            'rank'        => new HTML_Element_Selectbox_Rank($GLOBALS['Language']->getText('plugin_graphontrackersv5_property', 'rank'), 'chart[rank]', $this->getRank(), $this->getId(), $siblings),
            'dimensions'  => new HTML_Element_Columns(
                new HTML_Element_Input_Text($GLOBALS['Language']->getText('plugin_graphontrackersv5_property', 'width'), 'chart[width]', $this->getWidth(), 4),
                new HTML_Element_Input_Text($GLOBALS['Language']->getText('plugin_graphontrackersv5_property', 'height'), 'chart[height]', $this->getHeight(), 4)
            ),
        );
    }

    private function getSiblingsForRankSelectbox()
    {
        $siblings = array();
        $session  = new Tracker_Report_Session($this->renderer->report->id);
        $session->changeSessionNamespace("renderers.{$this->renderer->id}");

        $charts = $session->get('charts');
        uasort($charts, array(GraphOnTrackersV5_ChartFactory::instance(), 'sortArrayByRank'));
        foreach ($charts as $sibling) {
            $siblings[] = array(
                'id'   => $sibling['id'],
                'name' => $sibling['title'],
                'rank' => $sibling['rank']
            );
        }

        return $siblings;
    }

    /**
     * Update the properties of the chart
     *
     * @return bool true if the update is successful
     */
    public function update($row)
    {
        $session = self::getSession($this->renderer->report->id, $this->renderer->id);

        //Set in session
        $session->set("$this->id.rank", $row['rank']);
        $session->set("$this->id.title", $row['title']);
        $session->set("$this->id.description", $row['description']);
        if (isset($row['width'])) {
                $session->set("$this->id.width", $row['width']);
        }
        if (isset($row['height'])) {
                $session->set("$this->id.height", $row['height']);
        }

        $this->setRank($row['rank']);
        $this->setTitle($row['title']);
        $this->setDescription($row['description']);
        if (isset($row['width'])) {
                $this->setWidth($row['width']);
        }

        if (isset($row['height'])) {
                $this->setHeight($row['height']);
        }

        return $this->updateSpecificProperties($row);
    }

    public function exportToXml(SimpleXMLElement $root, $formsMapping)
    {
        $root->addAttribute('type', $this->getChartType());
        $root->addAttribute('width', $this->width);
        $root->addAttribute('height', $this->height);
        $root->addAttribute('rank', $this->rank);
        $cdata = new XML_SimpleXMLCDATAFactory();
        $cdata->insert($root, 'title', $this->title);
        if ($this->description != '') {
            $cdata->insert($root, 'description', $this->description);
        }
    }
    public function delete()
    {
        $this->getDao()->delete($this->id);
    }
    /**
     * Duplicate the chart
     */
    public function duplicate($from_chart, $field_mapping)
    {
        return $this->getDao()->duplicate($from_chart->id, $this->id, $field_mapping);
    }

    /**
     * Return the specific properties as a row
     * array('prop1' => 'value', 'prop2' => 'value', ...)
     * @return array
     */
    abstract public function getSpecificRow();

    /**
     * Return the chart type (gantt, bar, pie, ...)
     */
    abstract public function getChartType();

    /**
     * @return GraphOnTrackersV5_Engine The engine associated to the concrete chart
     */
    abstract protected function getEngine();

    /**
     * @return ChartDataBuilderV5 The data builder associated to the concrete chart
     */
    abstract protected function getChartDataBuilder($artifacts);

    /**
     * Allow update of the specific properties of the concrete chart
     * @return bool true if the update is successful
     */
    abstract protected function updateSpecificProperties($row);

    /**
     * Creates an array of specific properties of chart
     *
     * @return array containing the properties
     */
    abstract protected function arrayOfSpecificProperties();

    /**
     * Sets the specific properties of the concrete chart from XML
     *
     * @param SimpleXMLElement $xml characterising the chart
     * @param array $formsMapping associating xml IDs to real fields
     */
    abstract public function setSpecificPropertiesFromXML($xml, $formsMapping);

     /**
     * User as permission to visualize the chart
     */
    abstract public function userCanVisualize();

    /**
     * Create an instance of the chart
     * @return GraphOnTrackersV5_Chart
     */
    abstract public static function create($renderer, $id, $rank, $title, $description, $width, $height);

    /**
     * Get the dao of the chart
     */
    abstract protected function getDao();

    public function getContent()
    {
        $content          = '';
        $store_in_session = false;

        if ($this->isGraphDrawnByD3()) {
            $content .= $this->fetchContentD3Graph();
        } else {
            $content .= $this->fetchContentJPGraph($store_in_session);
        }

        return $content;
    }

    public function getWidgetContent()
    {
        $content = $this->fetchAdditionnalButton();
        $content .= $this->getContent();
        $content .= $this->renderer->fetchWidgetGoToReport();

        return $content;
    }

    private function isGraphDrawnByD3()
    {
        $d3_visitor = new D3CompatibleChartVisitor();
        return $this->accept($d3_visitor);
    }

    private function fetchContentJPGraph($store_in_session)
    {
        $content = $this->fetch($store_in_session);
        $content .= '<br />';

        return $content;
    }

    private function fetchContentD3Graph()
    {
        return $this->fetchGraphAnchor('');
    }

    private function getAvailableDashboardsForUser(PFUser $user)
    {
        $user_dashboard_retriever = new UserDashboardRetriever(
            new UserDashboardDao(
                new DashboardWidgetDao(
                    new WidgetFactory(
                        UserManager::instance(),
                        new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
                        EventManager::instance()
                    )
                )
            )
        );

        return $user_dashboard_retriever->getAllUserDashboards($user);
    }

    private function getAvailableDashboardsForProject($project)
    {
        $project_dashboard_retriever = new ProjectDashboardRetriever(
            new ProjectDashboardDao(
                new DashboardWidgetDao(
                    new WidgetFactory(
                        UserManager::instance(),
                        new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
                        EventManager::instance()
                    )
                )
            )
        );

        return $project_dashboard_retriever->getAllProjectDashboards($project);
    }
}
