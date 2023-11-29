<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\GraphOnTrackersV5\Widget\WidgetChartDao;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\MappingRegistry;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
abstract class GraphOnTrackersV5_Widget_Chart extends Widget
{
    public $chart_title;
    public $chart_id;

    public function __construct($id, $owner_id, $owner_type)
    {
        parent::__construct($id);
        $this->setOwner($owner_id, $owner_type);
    }

    public function getTitle()
    {
        return $this->chart_title ?: 'Tracker Chart';
    }

    public function getContent()
    {
        $chart = GraphOnTrackersV5_ChartFactory::instance()->getChart(
            null,
            $this->chart_id,
            false
        );

        if ($chart && $chart->getRenderer()->getReport()->getTracker()->userCanView()) {
            $content = $chart->getWidgetContent();
        } else {
            $content = '<em>Chart does not exist</em>';
        }

        return $content;
    }

    public function isAjax()
    {
        return false;
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="title-' . $purifier->purify($widget_id) . '">
                    ' . $purifier->purify(_('Title')) . '
                </label>
                <input type="text"
                       class="tlp-input"
                       id="title-' . $purifier->purify($widget_id) . '"
                       name="chart[title]"
                       value="' . $purifier->purify($this->getTitle()) . '">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="chart-id-' . $purifier->purify($widget_id) . '">
                    Chart Id <i class="fa fa-asterisk"></i>
                </label>
                <input type="number"
                       size="5"
                       class="tlp-input"
                       id="chart-id-' . $purifier->purify($widget_id) . '"
                       name="chart[chart_id]"
                       value="' . $purifier->purify($this->chart_id) . '"
                       required
                       placeholder="123">
            </div>
            ';
    }

    public function getInstallPreferences()
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="widget-chart-title">' . $purifier->purify(_('Title')) . '</label>
                <input type="text"
                       class="tlp-input"
                       id="widget-chart-title"
                       name="chart[title]"
                       value="' . $purifier->purify($this->getTitle()) . '">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="widget-chart-id">
                    Chart Id <i class="fa fa-asterisk"></i>
                </label>
                <input type="number"
                       size="5"
                       class="tlp-input"
                       id="widget-chart-id"
                       name="chart[chart_id]"
                       required
                       placeholder="123">
            </div>
            ';
    }

    public function cloneContent(
        Project $template_project,
        Project $new_project,
        $id,
        $owner_id,
        $owner_type,
        MappingRegistry $mapping_registry,
    ) {
        $dao = new WidgetChartDao();

        if (! $mapping_registry->hasCustomMapping(GraphOnTrackersV5_ChartFactory::MAPPING_KEY)) {
            return $dao->cloneContent(
                (int) $this->owner_id,
                (string) $this->owner_type,
                (int) $owner_id,
                (string) $owner_type
            );
        }

        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());

        return $transaction_executor->execute(
            function () use ($id, $dao, $owner_id, $owner_type, $mapping_registry): int {
                $data = $dao->searchContent($this->owner_id, $this->owner_type, (int) $id);
                if (! $data) {
                    return $dao->cloneContent(
                        $this->owner_id,
                        $this->owner_type,
                        (int) $owner_id,
                        (string) $owner_type
                    );
                }

                $item_mapping = $mapping_registry->getCustomMapping(GraphOnTrackersV5_ChartFactory::MAPPING_KEY);
                if (! isset($item_mapping[$data['chart_id']])) {
                    return $dao->insertContent(
                        (int) $owner_id,
                        (string) $owner_type,
                        $data['title'],
                        $data['chart_id'],
                    );
                }

                return $dao->insertContent(
                    (int) $owner_id,
                    (string) $owner_type,
                    $data['title'],
                    $item_mapping[$data['chart_id']],
                );
            }
        );
    }

    public function loadContent($id)
    {
        $sql = "SELECT * FROM plugin_graphontrackersv5_widget_chart WHERE owner_id = " . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "' AND id = " . db_ei($id);
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data              = db_fetch_array($res);
            $this->chart_title = $data['title'];
            $this->chart_id    = $data['chart_id'];
            $this->content_id  = $id;
        }
    }

    public function create(Codendi_Request $request)
    {
        $content_id = false;
        $vId        = new Valid_UInt('chart_id');
        $vId->setErrorMessage("Can't add empty chart id");
        $vId->required();
        if ($request->validInArray('chart', $vId)) {
            $chart      = $request->get('chart');
            $sql        = 'INSERT INTO plugin_graphontrackersv5_widget_chart (owner_id, owner_type, title, chart_id) VALUES (' . db_ei($this->owner_id) . ", '" . db_es($this->owner_type) . "', '" . db_escape_string($chart['title']) . "', " . db_escape_int($chart['chart_id']) . ")";
            $res        = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }

    public function updatePreferences(Codendi_Request $request)
    {
        $done       = false;
        $vContentId = new Valid_UInt('content_id');
        $vContentId->required();
        if (($chart = $request->get('chart')) && $request->valid($vContentId)) {
            $vId = new Valid_UInt('chart_id');
            if ($request->validInArray('chart', $vId)) {
                $id = " chart_id   = " . db_escape_int($chart['chart_id']) . " ";
            } else {
                $id = '';
            }

            $vTitle = new Valid_String('title');
            if ($request->validInArray('chart', $vTitle)) {
                $title = " title = '" . db_escape_string($chart['title']) . "' ";
            } else {
                $title = '';
            }

            if ($id || $title) {
                $sql  = "UPDATE plugin_graphontrackersv5_widget_chart SET " . $title . ", " . $id . " WHERE owner_id = " . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "' AND id = " . db_ei((int) $request->get('content_id'));
                $res  = db_query($sql);
                $done = true;
            }
        }
        return $done;
    }

    public function destroy($id)
    {
        $sql = 'DELETE FROM plugin_graphontrackersv5_widget_chart WHERE id = ' . db_ei($id) . ' AND owner_id = ' . db_ei($this->owner_id) . " AND owner_type = '" . db_es($this->owner_type) . "'";
        db_query($sql);
    }

    public function isUnique()
    {
        return false;
    }

    public function getCategory()
    {
        return dgettext('tuleap-tracker', 'Trackers');
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
