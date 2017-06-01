<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'common/widget/Widget.class.php';

/**
 * Widget_TrackerRenderer
 *
 * Tracker Renderer
 */
abstract class Tracker_Widget_Renderer extends Widget {
    var $renderer_title;
    var $renderer_id;

    function __construct($id, $owner_id, $owner_type) {
        parent::__construct($id);
        $this->setOwner($owner_id, $owner_type);
    }

    function getTitle() {
        $hp = Codendi_HTMLPurifier::instance();
        return $this->renderer_title ?
            $hp->purify($this->renderer_title, CODENDI_PURIFIER_CONVERT_HTML) :
            dgettext('tuleap-tracker', 'Tracker renderer');
    }

    function getContent() {
        $renderer = $this->getRenderer();
        if ($renderer) {
            return $renderer->fetchWidget($this->getCurrentUser());
        } else {
            return '<em>Renderer does not exist</em>';
        }
    }

    /**
     * Obtain the report renderer.
     *
     * @return Tracker_Report_Renderer
     */
    function getRenderer() {
        $store_in_session = false;
        $arrf             = Tracker_Report_RendererFactory::instance();
        $renderer         = $arrf->getReportRendererById($this->renderer_id, null, $store_in_session);
        if ($renderer) {
            $tracker = $renderer->report->getTracker();
            $project = $tracker->getProject();
            if ($tracker->isActive() && $project->isActive()) {
                return $renderer;
            }
        }
        return null;
    }

    function isAjax() {
        return true;
    }

    function getInstallPreferences() {
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
                       name="renderer[title]"
                       value="'. $this->getTitle() .'"
                       placeholder="'. $purifier->purify(dgettext('tuleap-tracker', 'Tracker renderer')) .'">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="renderer-id-'. (int)$widget_id .'">
                    '. $purifier->purify(dgettext('tuleap-tracker', 'Renderer id')) .'
                    <i class="fa fa-asterisk"></i>
                </label>
                <input type="number"
                       size="5"
                       class="tlp-input"
                       id="renderer-id-'. (int)$widget_id .'"
                       name="renderer[renderer_id]"
                       value="'. $purifier->purify($this->renderer_id) .'"
                       required
                       placeholder="123">
            </div>
            ';
    }

    function getPreferences() {
        $hp = Codendi_HTMLPurifier::instance();

        $prefs  = '';
        $prefs .= '<table><tr><td>Title:</td><td><input type="text" class="textfield_medium" name="renderer[title]" value="'. $hp->purify($this->renderer_title, CODENDI_PURIFIER_CONVERT_HTML) .'" /></td></tr>';
        $prefs .= '<tr><td>Renderer Id:</td><td>';

        $prefs .= '<input type="text" name="renderer[renderer_id]" value="'. ((int)$this->renderer_id ? (int)$this->renderer_id : '') .'" />';

        $prefs .= '</td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }

    function cloneContent($id, $owner_id, $owner_type) {
        $sql = "INSERT INTO tracker_widget_renderer (owner_id, owner_type, title, renderer_id) 
        SELECT  ". $owner_id .", '". $owner_type ."', title, renderer_id
        FROM widget_renderer
        WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' ";
        $res = db_query($sql);
        return db_insertid($res);
    }

    function loadContent($id) {
        $sql = "SELECT * FROM tracker_widget_renderer WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". $id;
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data = db_fetch_array($res);
            $this->renderer_title = $data['title'];
            $this->renderer_id    = $data['renderer_id'];
            $this->content_id = $id;
        }
    }

    function create($request) {
        $content_id = false;
        $vId = new Valid_Uint('renderer_id');
        $vId->setErrorMessage("Can't add empty renderer id");
        $vId->required();
        if($request->validInArray('renderer', $vId)) {
            $renderer = $request->get('renderer');
            $sql = 'INSERT INTO tracker_widget_renderer (owner_id, owner_type, title, renderer_id) VALUES ('. $this->owner_id .", '". $this->owner_type ."', '". db_escape_string($renderer['title']) ."', ". db_escape_int($renderer['renderer_id']) .")";
            $res = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }

    function updatePreferences($request) {
        $done = false;
        $vContentId = new Valid_UInt('content_id');
        $vContentId->required();
        if (($renderer = $request->get('renderer')) && $request->valid($vContentId)) {
            $vId = new Valid_Uint('renderer_id');
            if($request->validInArray('renderer', $vId)) {
                $id = " renderer_id   = ". db_escape_int($renderer['renderer_id']) ." ";
            } else {
                $id = '';
            }

            $vTitle = new Valid_String('title');
            if($request->validInArray('renderer', $vTitle)) {
                $title = " title = '". db_escape_string($renderer['title']) ."' ";
            } else {
                $title = '';
            }

            if ($id || $title) {
                $sql = "UPDATE tracker_widget_renderer SET ". $title .", ". $id ." WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". (int)$request->get('content_id');
                $res = db_query($sql);
                $done = true;
            }
        }
        return $done;
    }

    function destroy($id) {
        $sql = 'DELETE FROM tracker_widget_renderer WHERE id = '. $id .' AND owner_id = '. $this->owner_id ." AND owner_type = '". $this->owner_type ."'";
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
