<?php
/**
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
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

require_once('Widget.class.php');

/**
* Widget_ImageViewer
*
* Display an image
*
*/
class Widget_ImageViewer extends Widget {
    var $image_title;
    var $image_url;
    function __construct($id, $owner_id, $owner_type) {
        parent::__construct($id);
        $this->setOwner($owner_id, $owner_type);
    }
    function getTitle() {
        $hp = Codendi_HTMLPurifier::instance();
        return $this->image_title ?  $hp->purify($this->image_title, CODENDI_PURIFIER_CONVERT_HTML)  : 'Image';
    }
    function getContent() {
        $hp = Codendi_HTMLPurifier::instance();
        $content = '';
        if ($this->image_url) {
            $content .= '<div style="text-align:center">';
            $content .= '<img src="'.  $hp->purify($this->image_url, CODENDI_PURIFIER_CONVERT_HTML)  .'" alt="'. $this->getTitle() .'" />';
            $content .= '</div>';
        }
        return $content;
    }

    public function getContentForBurningParrot()
    {
        if (!$this->image_url) {
            return '';
        }

        $hp = Codendi_HTMLPurifier::instance();

        return '<div class="dashboard-widget-imageviewver-content"><img class="dashboard-widget-imageviewver-img"
            src="' . $hp->purify($this->image_url) . '"
            alt="' . $hp->purify($this->getTitle()) . '" /></div>';
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
                       name="image[title]"
                       value="'. $this->getTitle() .'"
                       placeholder="'. $purifier->purify(_('Image')) .'">
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="url-'. (int)$widget_id .'">
                    URL <i class="fa fa-asterisk"></i>
                </label>
                <input type="text"
                       class="tlp-input"
                       id="url-'. (int)$widget_id .'"
                       name="image[url]"
                       value="'. $purifier->purify($this->image_url) .'"
                       pattern="https?://.*"
                       title="'. $purifier->purify(_('Please, enter a http:// or https:// link')) .'"
                       required
                       placeholder="https://example.com/image.png">
            </div>
            ';
    }

    function getPreferences() {
        $hp = Codendi_HTMLPurifier::instance();
        $prefs  = '';
        $prefs .= '<table>';
        $prefs .= '<tr><td>Title:</td><td><input type="text" class="textfield_medium" name="image[title]" value="'. $this->getTitle() .'" /></td></tr>';
        $prefs .= '<tr><td>Url:</td><td><input type="text" class="textfield_medium" name="image[url]" value="'. $hp->purify($this->image_url, CODENDI_PURIFIER_CONVERT_HTML) .'" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    function getInstallPreferences() {
        $prefs  = '';
        $prefs .= '<table>';
        $prefs .= '<tr><td>Title:</td><td><input type="text" class="textfield_medium" name="image[title]" value="'. $this->getTitle() .'" /></td></tr>';
        $prefs .= '<tr><td>Url:</td><td><input type="text" class="textfield_medium" name="image[url]" placeholder="https://example.com/image.jpeg" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    function cloneContent($id, $owner_id, $owner_type) {
        $sql = "INSERT INTO widget_image (owner_id, owner_type, title, url) 
        SELECT  ". db_ei($owner_id) .", '". db_es($owner_type) ."', title, url
        FROM widget_image
        WHERE owner_id = ". db_ei($this->owner_id) ." AND owner_type = '". db_es($this->owner_type) ."' ";
        $res = db_query($sql);
        return db_insertid($res);
    }
    function loadContent($id) {
        $sql = "SELECT * FROM widget_image WHERE owner_id = ". db_ei($this->owner_id) ." AND owner_type = '". db_es($this->owner_type) ."' AND id = ". db_ei($id);
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data = db_fetch_array($res);
            $this->image_title = $data['title'];
            $this->image_url   = $data['url'];
            $this->content_id = $id;
        }
    }
    function create(&$request) {
        $content_id = false;
        $vUrl = new Valid_HTTPURI('url');
        $vUrl->setErrorMessage($GLOBALS['Language']->getText('widget_imageviewer', 'invalid_url'));
        $vUrl->required();
        if($request->validInArray('image', $vUrl)) {
            $image = $request->get('image');
            $vTitle = new Valid_String('title');
            $vTitle->required();
            if (!$request->validInArray('image', $vTitle)) {
                $image['title'] = 'Image';
            }
            $sql = 'INSERT INTO widget_image (owner_id, owner_type, title, url) VALUES ('. db_ei($this->owner_id) .", '". db_es($this->owner_type) ."', '". db_escape_string($image['title']) ."', '". db_escape_string($image['url']) ."')";
            $res = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }
    function updatePreferences(&$request) {
        $done = false;
        $vContentId = new Valid_UInt('content_id');
        $vContentId->required();
        if (($image = $request->get('image')) && $request->valid($vContentId)) {
            $vUrl = new Valid_String('url');
            if($request->validInArray('image', $vUrl)) {
                $url = " url   = '". db_escape_string($image['url']) ."' ";
            } else {
                $url = '';
            }

            $vTitle = new Valid_String('title');
            if($request->validInArray('image', $vTitle)) {
                $title = " title = '". db_escape_string($image['title']) ."' ";
            } else {
                $title = '';
            }

            if ($url || $title) {
                $sql = "UPDATE widget_image SET ". $title .", ". $url ." WHERE owner_id = ". db_ei($this->owner_id) ." AND owner_type = '". db_es($this->owner_type) ."' AND id = ". db_ei($request->get('content_id'));
                $res = db_query($sql);
                $done = true;
            }
        }
        return $done;
    }
    function destroy($id) {
        $sql = 'DELETE FROM widget_image WHERE id = '. db_ei($id) .' AND owner_id = '. db_ei($this->owner_id) ." AND owner_type = '". db_es($this->owner_type) ."'";
        db_query($sql);
    }
    function isUnique() {
        return false;
    }
}
