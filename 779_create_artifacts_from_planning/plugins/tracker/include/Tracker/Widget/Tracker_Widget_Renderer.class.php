<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
require_once dirname(__FILE__).'/../Report/Tracker_Report_RendererFactory.class.php';

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
        return $this->renderer_title ?  $hp->purify($this->renderer_title, CODENDI_PURIFIER_CONVERT_HTML)  : 'Tracker Renderer';
    }

    function getContent() {
        $content = '';
        $arrf = Tracker_Report_RendererFactory::instance();
        $store_in_session = false;
        if ($renderer = $arrf->getReportRendererById($this->renderer_id, null, $store_in_session)) {
            echo $renderer->fetchWidget();
        } else {
            echo '<em>Renderer does not exist</em>';
        }
        return $content;
    }

    function isAjax() {
        return true;
    }

    function getInstallPreferences($owner_id) {
        return $this->getPreferences($owner_id);
    }

    function getPreferences($owner_id) {
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
