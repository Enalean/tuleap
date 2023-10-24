<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\MappingRegistry;
use Tuleap\Tracker\Report\WidgetAdditionalButtonPresenter;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class GraphOnTrackersV5_Renderer extends Tracker_Report_Renderer
{
    protected $charts;
    protected $chart_to_edit;
    protected $plugin;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    private UserManager $user_manager;
    public function __construct($id, $report, $name, $description, $rank, $plugin, UserManager $user_manager, Tracker_FormElementFactory $form_element_factory)
    {
        parent::__construct($id, $report, $name, $description, $rank);
        $this->charts               = null;
        $this->chart_to_edit        = null;
        $this->plugin               = $plugin;
        $this->user_manager         = $user_manager;
        $this->form_element_factory = $form_element_factory;
    }

    public function initiateSession()
    {
        $this->report_session = new Tracker_Report_Session($this->report->id);
        $this->report_session->changeSessionNamespace('renderers');
    }

    public function setCharts($charts)
    {
        $this->charts = $charts;
    }

    public function getCharts()
    {
        return $this->charts;
    }

    /**
     * Delete the renderer
     */
    public function delete()
    {
        foreach ($this->getChartFactory()->getCharts($this) as $chart) {
            $this->getChartFactory()->deleteChart(
                $this->id,
                $chart->getId(),
                $this->report->userCanUpdate($this->user_manager->getCurrentUser())
            );
        }
    }

    /**
     * Fetch content of the renderer
     * @param array $matching_ids
     * @param HTTPRequest $request
     * @return string
     */
    public function fetch($matching_ids, $request, $report_can_be_modified, PFUser $user)
    {
        $html = '';
        $this->initiateSession();
        $readonly = ! $report_can_be_modified || $user->isAnonymous();

        if (! $readonly && $this->chart_to_edit) {
            $url   = '?' . http_build_query([
                'report'   => $this->report->id,
                'renderer' => $this->id,
            ]);
            $html .= '<p><a href="' . $url . '">&laquo; ' . dgettext('tuleap-graphontrackersv5', 'Go back to charts') . '</a></p>';
            $html .= '<form action="' . $url . '" name="edit_chart_form" method="post">';
            $html .= '<input type="hidden" name="func" VALUE="renderer" />';
            $html .= '<input type="hidden" name="renderer_plugin_graphontrackersv5[edit_chart]" VALUE="' . $this->chart_to_edit->getId() . '" />';
            $html .= '<table>';
            $html .= '<thead>
                        <tr class="boxtable">
                            <th class="boxtitle">' . dgettext('tuleap-graphontrackersv5', 'Chart Properties') . '</th>
                            <th class="boxtitle">' . dgettext('tuleap-graphontrackersv5', 'Preview') . '</th>
                        </tr>
                      </thead>';
            $html .= '<tbody><tr valign="top"><td>';
            //{{{ Chart Properties
            foreach ($this->chart_to_edit->getProperties() as $prop) {
                $html .= '<p>' . $prop->render() . "</p>\n";
            }
            $html .= '<p style="text-align:center;"><input type="submit" name="renderer_plugin_graphontrackersv5[update_chart]" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" /></p>';
            //}}}
            $html .= '</td><td style="text-align:center">';
            //{{{ Chart Preview

            $html .= $this->chart_to_edit->getContent(false);
            //}}}
            $html .= '</tr>';
            $html .= '</tbody></table>';
            $html .= '</form>';
        } else {
            $in_dashboard = false;
            $html        .= $this->fetchCharts($user, $in_dashboard, $readonly);
        }
        return $html;
    }

    /**
     * Fetch content to be displayed in widget
     */
    public function fetchWidget(PFUser $user)
    {
        $html             = '';
        $in_dashboard     = $readonly = true;
        $store_in_session = false;
        if ($in_dashboard) {
            $html .= $this->fetchAdditionalButton($this->report->getTracker());
        }
        $html .= $this->fetchCharts($user, $in_dashboard, $readonly, $store_in_session);
        $html .= $this->fetchWidgetGoToReport();
        return $html;
    }

    protected function fetchCharts(
        PFUser $current_user,
        $in_dashboard = false,
        $readonly = false,
        $store_in_session = true,
    ): string {
        $html = '';

        if (! $readonly) {
            $html   .= '<div id="tracker_report_renderer_view_controls">';
            $html   .= '<div class="btn-group">';
            $html   .= '<a href="#" class="btn btn-mini dropdown-toggle" data-toggle="dropdown">';
            $html   .= '<i class="fa fa-plus"></i> ';
            $html   .= dgettext('tuleap-graphontrackersv5', 'Add a Chart');
            $html   .= ' <span class="caret"></span>';
            $html   .= '</a>';
            $html   .= '<ul class="dropdown-menu pull-right"> ';
            $url     = '?' . http_build_query([
                'report'   => $this->report->id,
                'renderer' => $this->id,
                'func'     => 'renderer',
            ]);
            $url_add = $url . '&amp;renderer_plugin_graphontrackersv5[add_chart]=';
            foreach ($this->getChartFactory()->getChartFactories() as $factory) {
                $html .= '<li>';
                $html .= '<a href="' . $url_add . $factory['chart_type'] . '">';
                $html .= $factory['title'];
                $html .= '</a>';
                $html .= '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<form action="" method="POST">
                <input type="hidden" name="func" VALUE="renderer" />
                <input type="hidden" name="renderer" VALUE="' . $this->id . '" />';
        }

        $report_charts = $this->getChartFactory()->getCharts($this);
        $matching_ids  = $this->report->getMatchingIds(null, $in_dashboard);
        assert(is_array($matching_ids));

        if ($this->widgetMustDisplayEmptyState($in_dashboard, $report_charts, $matching_ids)) {
            $renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../src/templates/dashboard');

            $html .= $renderer->renderToString("widget-empty-content-svg", null);
            return $html;
        }

        $html .= '<div class="tracker_report_renderer_graphontrackers_charts">';

        foreach ($report_charts as $chart) {
            $html .= '<div class="widget_report_graph">';
            $html .= $chart->fetchOnReport($this, $current_user, $readonly, $in_dashboard, $store_in_session);
            $html .= '</div>';
        }

        $html .= '</div>';

        if (! $readonly) {
            $html .= '</form>';
        }

        return $html;
    }

    private function widgetMustDisplayEmptyState(
        bool $in_dashboard,
        array $report_charts,
        array $matching_ids,
    ): bool {
        if ($in_dashboard === true) {
            if (count($report_charts) === 0) {
                return true;
            } elseif (isset($matching_ids['id']) && $matching_ids['id'] === '') {
                return true;
            }
        }

        return false;
    }

    private function fetchAdditionalButton()
    {
        $is_a_table_renderer = false;

        $html = $this->getTemplateRenderer()->renderToString(
            'widget-additionnal-button',
            new WidgetAdditionalButtonPresenter(
                $this->report->getTracker(),
                $is_a_table_renderer
            )
        );

        return $html;
    }

    private function getTemplateRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR . '/report');
    }

    /**
     * Process the request
     * @param HTTPRequest $request
     */
    public function processRequest(TrackerManager $tracker_manager, $request, PFUser $current_user)
    {
        $renderer_parameters = $request->get('renderer_plugin_graphontrackersv5');
        if ($renderer_parameters && is_array($renderer_parameters)) {
            if (isset($renderer_parameters['add_chart'])) {
                $this->chart_to_edit = $this->getChartFactory()
                    ->createChart($this, $renderer_parameters['add_chart']);
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?' . http_build_query([
                    'report' => $this->report->id,
                    'renderer' => $this->id,
                    'func' => 'renderer',
                    'renderer_plugin_graphontrackersv5[edit_chart]' => $this->chart_to_edit->id,
                ]));
            }

            if (isset($renderer_parameters['edit_chart']) && ! $current_user->isAnonymous()) {
                $this->chart_to_edit = $this->getChartFactory()
                    ->getChart($this, $renderer_parameters['edit_chart']);
                if (isset($renderer_parameters['update_chart']) && is_array($request->get('chart'))) {
                    $chart_data = $request->get('chart');
                    if ($this->chart_to_edit->update($chart_data)) {
                        //force the rank for all charts
                        $this->getChartFactory()->forceChartsRankInSession(
                            $this,
                            $this->chart_to_edit,
                            $chart_data['rank']
                        );
                        $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-graphontrackersv5', 'Graphic Report updated successfully'));
                    }
                }
                $this->report->display($tracker_manager, $request, $current_user);
            }

            if (isset($renderer_parameters['delete_chart']) && is_array($renderer_parameters['delete_chart']) && ! $current_user->isAnonymous()) {
                foreach ($renderer_parameters['delete_chart'] as $chart_id => $chart) {
                    if ($chart_id) {
                        $this->getChartFactory()->deleteChart($this, $chart_id, $this->report->userCanUpdate($current_user));
                    }
                }
            }

            if (isset($renderer_parameters['stroke'])) {
                $store_in_session = true;
                if ($request->exist('store_in_session')) {
                    $store_in_session = (bool) $request->get('store_in_session');
                }
                if (
                    $chart = $this->getChartFactory()
                        ->getChart($this, $renderer_parameters['stroke'], $store_in_session)
                ) {
                    $chart->stroke(! $store_in_session);
                    exit;
                }
            }
        }
    }

    /**
     * Duplicate the renderer
     */
    public function duplicate($from_renderer, $field_mapping, MappingRegistry $mapping_registry): void
    {
        $this->getChartFactory()->duplicate($from_renderer, $this, $field_mapping, $mapping_registry);
    }

    public function afterProcessRequest($engine, $request, $current_user)
    {
        if (! $this->chart_to_edit) {
            parent::afterProcessRequest($engine, $request, $current_user);
        }
    }

    protected function getChartFactory()
    {
        return GraphOnTrackersV5_ChartFactory::instance();
    }

    public function getType()
    {
        return 'plugin_graphontrackersv5';
    }

    /**
     * Transforms Tracker_Renderer into a SimpleXMLElement
     *
     * @param SimpleXMLElement $root the node to which the renderer is attached (passed by reference)
     */
    public function exportToXml(SimpleXMLElement $root, array $formsMapping)
    {
        parent::exportToXml($root, $formsMapping);

        $child = $root->addChild('charts');
        foreach ($this->getChartFactory()->getCharts($this) as $chart) {
            if ($chart instanceof GraphOnTrackersV5_Chart_CumulativeFlow) {
                if (! $this->form_element_factory->getUsedFormElementById($chart->getFieldId())) {
                    return;
                }
                $grandchild = $child->addChild('chart');
                $chart->exportToXML($grandchild, $formsMapping);
            } else {
                $grandchild = $child->addChild('chart');
                $chart->exportToXML($grandchild, $formsMapping);
            }
        }
    }

    /**
     * Finnish saving renderer to database by creating charts
     *
     * @param Tracker_Report_Renderer $renderer containing the charts
     */
    public function afterSaveObject(Tracker_Report_Renderer $renderer)
    {
        $cf = $this->getChartFactory();
        foreach ($renderer->getCharts() as $chart) {
            $chartDB = $cf->createDb($this->id, $chart);
        }
    }

   /**
    * Set the session
    *
    */
    public function setSession($renderer_id = null)
    {
        if (! $renderer_id) {
            $renderer_id = $this->id;
        }
        $this->report_session->set("{$this->id}.name", $this->name);
        $this->report_session->set("{$this->id}.description", $this->description);
        //$this->report_session->set("{$this->id}.plugin", $this->plugin);
        $this->report_session->set("{$this->id}.rank", $this->rank);
    }

    /**
     * Update the renderer
     *
     * @return bool true if success, false if failure
     */
    public function update()
    {
        $success = true;
        //Save charts
        $charts = $this->getChartFactory()->getCharts($this);
        //$this->report_session->changeSessionNamespace("renderers.{$this->id}");
        $chartsInSession = $this->report_session->get("$this->id.charts");
        if ($chartsInSession) {
            //Delete in db charts removed in session
            foreach ($chartsInSession as $id => $row) {
                if ($row === 'removed') {
                    $this->getChartFactory()->deleteDb($this, $id);
                }
            }
        }

        foreach ($charts as $chart_id => $chart) {
            //Update charts
            if ($chart_id > 0) {
                $method = 'updateDb';
            } else {
                $method = 'createDb';
            }
            $this->getChartFactory()->$method($this->id, $chart);
        }
        return $success;
    }

    /**
     * Create a renderer
     *
     * @return bool true if success, false if failure
     */
    public function create()
    {
        $success = true;
        $rrf     = Tracker_Report_RendererFactory::instance();

        if ($renderer_id = $rrf->saveRenderer($this->report, $this->name, $this->description, $this->getType())) {
            //Save charts
            $charts = $this->getChartFactory()
                ->getCharts($this);

            foreach ($charts as $chart_id => $chart) {
                //Add new chart
                $this->getChartFactory()
                    ->createDb(
                        $renderer_id,
                        $chart
                    );
            }
        }
        return $success;
    }

    public function getIcon()
    {
        return 'far fa-chart-bar';
    }

    public function getJavascriptDependencies(): array
    {
        return [
            ['file' => $this->getAssets()->getFileURL('graphontrackersv5.js')],
        ];
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        return new CssAssetCollection([new CssAssetWithoutVariantDeclinaisons($this->getAssets(), 'style')]);
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/graphontrackersv5'
        );
    }
}
