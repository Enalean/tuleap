<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
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

require_once('data-access/GraphOnTrackersV5_ChartFactory.class.php');

/**
* GraphOnTrackersV5_Widget_Chart
*
* Tracker Chart
*/
abstract class GraphOnTrackersV5_Widget_Chart extends Widget {
    var $chart_title;
    var $chart_id;

    public function __construct($id, $owner_id, $owner_type)
    {
        parent::__construct($id);
        $this->setOwner($owner_id, $owner_type);
    }

    function getTitle() {
        $hp = Codendi_HTMLPurifier::instance();
        return $this->chart_title ?  $hp->purify($this->chart_title, CODENDI_PURIFIER_CONVERT_HTML)  : 'Tracker Chart';
    }

    public function getContent() {
        $chart = GraphOnTrackersV5_ChartFactory::instance()->getChart(
            null,
            $this->chart_id,
            false
        );

        if ($chart) {
            $content = $chart->getWidgetContent();
        } else {
            $content = '<em>Chart does not exist</em>';
        }

        return $content;
    }

    public function isAjax() {
        return false;
    }

    public function getInstallPreferences() {
        return $this->getPreferences();
    }

    public function getPreferencesForBurningParrot($widget_id)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="title-'. (int)$widget_id .'">'. $purifier->purify(_('Title')) .'</label>
                <input type="text"
                       class="tlp-input"
                       id="title-'. (int)$widget_id .'"
                       name="chart[title]"
                       value="'. $this->getTitle() .'">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="chart-id-'. (int)$widget_id .'">
                    Chart Id <i class="fa fa-asterisk"></i>
                </label>
                <input type="number"
                       size="5"
                       class="tlp-input"
                       id="chart-id-'. (int)$widget_id .'"
                       name="chart[chart_id]"
                       value="'. $purifier->purify($this->chart_id) .'"
                       required
                       placeholder="123">
            </div>
            ';
    }

    public function getPreferences() {
        $hp = Codendi_HTMLPurifier::instance();

        $prefs  = '';
        $prefs .= '<table><tr><td>Title:</td><td><input type="text" class="textfield_medium" name="chart[title]" value="'. $hp->purify($this->chart_title, CODENDI_PURIFIER_CONVERT_HTML) .'" /></td></tr>';
        $prefs .= '<tr><td>Chart Id:</td><td>';

        $prefs .= '<input name="chart[chart_id]" type="text" value="'. $hp->purify($this->chart_id, CODENDI_PURIFIER_CONVERT_HTML) .'" />';

        $prefs .= '</td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }

    function cloneContent($id, $owner_id, $owner_type) {
        $sql = "INSERT INTO plugin_graphontrackersv5_widget_chart (owner_id, owner_type, title, chart_id) 
        SELECT  ". $owner_id .", '". $owner_type ."', title, chart_id
        FROM plugin_graphontrackersv5_widget_chart
        WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' ";
        $res = db_query($sql);
        return db_insertid($res);
    }
    function loadContent($id) {
        $sql = "SELECT * FROM plugin_graphontrackersv5_widget_chart WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". $id;
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data = db_fetch_array($res);
            $this->chart_title = $data['title'];
            $this->chart_id    = $data['chart_id'];
            $this->content_id = $id;
        }
    }
    function create(&$request) {
        $content_id = false;
        $vId = new Valid_Uint('chart_id');
        $vId->setErrorMessage("Can't add empty chart id");
        $vId->required();
        if($request->validInArray('chart', $vId)) {
            $chart = $request->get('chart');
            $sql = 'INSERT INTO plugin_graphontrackersv5_widget_chart (owner_id, owner_type, title, chart_id) VALUES ('. $this->owner_id .", '". $this->owner_type ."', '". db_escape_string($chart['title']) ."', ". db_escape_int($chart['chart_id']) .")";
            $res = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }
    function updatePreferences(&$request) {
        $done = false;
        $vContentId = new Valid_UInt('content_id');
        $vContentId->required();
        if (($chart = $request->get('chart')) && $request->valid($vContentId)) {
            $vId = new Valid_Uint('chart_id');
            if($request->validInArray('chart', $vId)) {
                $id = " chart_id   = ". db_escape_int($chart['chart_id']) ." ";
            } else {
                $id = '';
            }

            $vTitle = new Valid_String('title');
            if($request->validInArray('chart', $vTitle)) {
                $title = " title = '". db_escape_string($chart['title']) ."' ";
            } else {
                $title = '';
            }

            if ($id || $title) {
                $sql = "UPDATE plugin_graphontrackersv5_widget_chart SET ". $title .", ". $id ." WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". (int)$request->get('content_id');
                $res = db_query($sql);
                $done = true;
            }
        }
        return $done;
    }
    function destroy($id) {
        $sql = 'DELETE FROM plugin_graphontrackersv5_widget_chart WHERE id = '. $id .' AND owner_id = '. $this->owner_id ." AND owner_type = '". $this->owner_type ."'";
        db_query($sql);
    }
    function isUnique() {
        return false;
    }

    function getCategory() {
        return 'trackers';
    }
}
?>
