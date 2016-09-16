<?php
/*
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
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

require_once('Widget_WikiPage.class.php');
require_once('Widget.class.php');
require_once('common/wiki/lib/WikiPage.class.php');

/**
* widget_wikipage
* 
* Allow to follow a twitter group_id
* 
*/
class Widget_WikiPage extends Widget {
    
    protected $wikipage_group_id;
    protected $wikipage_wiki_page;
    protected $wikipage_title;
    
    function Widget_WikiPage($id, $owner_id, $owner_type) {
        $this->Widget($id);
        $this->setOwner($owner_id, $owner_type);
    }
    function getTitle() {
        $hp = Codendi_HTMLPurifier::instance();
        return $this->wikipage_title ?  $hp->purify($this->wikipage_title, CODENDI_PURIFIER_CONVERT_HTML)  : 'Wiki Page';
    }
    function getContent() {
        $hp = Codendi_HTMLPurifier::instance();
        $content = '';
        $p = new WikiPage($this->wikipage_group_id, $this->wikipage_wiki_page);
        //Todo: prevent wiki initialisation
        //Todo: prevent whole wiki permission bypassing
        //Todo: fix internal link (make them link to /wiki/ instead of current location (eg: /my/widgets )
        //Todo: display a link to go to the page
        //Todo: check that page exists before doing something
        if ($p->isAutorized(UserManager::instance()->getCurrentUser()->getId())) {
            $content .= $p->render($lite=true, $full_screen=true);
        }
        return $content;
    }
    function isAjax() {
        return true;
    }
    function hasPreferences() {
        return true;
    }
    function getPreferences() {
        $hp = Codendi_HTMLPurifier::instance();
        $prefs  = '';
        $prefs .= '<table><tr><td>Title:</td><td><input type="text" class="textfield_medium" name="WikiPage[title]" value="'. $hp->purify($this->wikipage_title, CODENDI_PURIFIER_CONVERT_HTML) .'" /></td></tr>';
        $prefs .= '<tr><td>Project id:</td><td><input type="text" class="textfield_medium" name="WikiPage[group_id]" value="'. $hp->purify($this->wikipage_group_id, CODENDI_PURIFIER_CONVERT_HTML) .'" /></td></tr>';
        $prefs .= '<tr><td>Wiki page:</td><td><input type="text" class="textfield_medium" name="WikiPage[wiki_page]" value="'. $hp->purify($this->wikipage_wiki_page, CODENDI_PURIFIER_CONVERT_HTML) .'" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    function getInstallPreferences() {
        $prefs  = '';
        $prefs .= '<table>';
        $prefs .= '<tr><td>Title:</td><td><input type="text" class="textfield_medium" name="WikiPage[title]" value="Wiki" /></td></tr>';
        $default_group_id = '';
        if ($this->owner_type == WidgetLayoutManager::OWNER_TYPE_GROUP) {
            $default_group_id = $this->owner_id;
        }
        $prefs .= '<tr><td>Project id:</td><td><input type="text" class="textfield_medium" name="WikiPage[group_id]" value="'. $default_group_id .'" /></td></tr>';
        $prefs .= '<tr><td>Wiki page:</td><td><input type="text" class="textfield_medium" name="WikiPage[wiki_page]" value="" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    function cloneContent($id, $owner_id, $owner_type) {
        $sql = "INSERT INTO widget_wikipage (owner_id, owner_type, title, group_id, wiki_page) 
        SELECT  ". db_ei($owner_id) .", '". db_es($owner_type) ."', title, group_id, wiki_page
        FROM widget_wikipage
        WHERE owner_id = ". db_ei($this->owner_id) ." AND owner_type = '". db_es($this->owner_type) ."' ";
        $res = db_query($sql);
        return db_insertid($res);
    }
    function loadContent($id) {
        $sql = "SELECT * FROM widget_wikipage WHERE owner_id = ". db_ei($this->owner_id) ." AND owner_type = '". db_es($this->owner_type) ."' AND id = ". db_ei($id);
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data = db_fetch_array($res);
            $this->wikipage_title     = $data['title'];
            $this->wikipage_group_id  = $data['group_id'];
            $this->wikipage_wiki_page = $data['wiki_page'];
            $this->content_id = $id;
        }
    }
    function create($request) {
        $content_id = false;
        $vGroup_id = new Valid_String('group_id');
        $vGroup_id->setErrorMessage("Can't add empty WikiPage group_id");
        $vGroup_id->required();
        if($request->validInArray('WikiPage', $vGroup_id)) {
            $WikiPage = $request->get('WikiPage');
            $vTitle = new Valid_String('title');
            $vTitle->required();
            if (!$request->validInArray('WikiPage', $vTitle)) {
                require_once('common/rss/libs/SimplePie/simplepie.inc');
                if (!is_dir($GLOBALS['codendi_cache_dir'] .'/WikiPage')) {
                    mkdir($GLOBALS['codendi_cache_dir'] .'/WikiPage');
                }
                $WikiPage_reader = new SimplePie($this->getFeedUrl($WikiPage['group_id']), $GLOBALS['codendi_cache_dir'] .'/WikiPage', null, $GLOBALS['sys_proxy']);
                $WikiPage['title'] = $WikiPage_reader->get_title();
            }
            $sql = 'INSERT INTO widget_wikipage (owner_id, owner_type, title, group_id, wiki_page) 
                    VALUES ('. dbe_i($this->owner_id) .", '". db_es($this->owner_type) ."', '". db_escape_string($WikiPage['title']) ."', '". db_escape_string($WikiPage['group_id']) ."', '". db_escape_string($WikiPage['wiki_page']) ."')";
            $res = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }
    function updatePreferences($request) {
        $done = false;
        $vContentId = new Valid_UInt('content_id');
        $vContentId->required();
        if (($WikiPage = $request->get('WikiPage')) && $request->valid($vContentId)) {
            $vGroup_id = new Valid_String('group_id');
            if($request->validInArray('WikiPage', $vGroup_id)) {
                $group_id = " group_id   = '". db_escape_string($WikiPage['group_id']) ."' ";
            } else {
                $group_id = 'group_id=group_id';
            }

            $vTitle = new Valid_String('title');
            if($request->validInArray('WikiPage', $vTitle)) {
                $title = " title = '". db_escape_string($WikiPage['title']) ."' ";
            } else {
                $title = 'title=title';
            }

            $vWikiPage = new Valid_String('wiki_page');
            if($request->validInArray('WikiPage', $vWikiPage)) {
                $wiki_page = " wiki_page = '". db_escape_string($WikiPage['wiki_page']) ."' ";
            } else {
                $wiki_page = 'wiki_page=wiki_page';
            }

            if ($group_id || $title || $wiki_page) {
                $sql = "UPDATE widget_wikipage 
                        SET $title, $group_id, $wiki_page 
                        WHERE owner_id = ". $this->owner_id ." 
                          AND owner_type = '". $this->owner_type ."' 
                          AND id = ". (int)$request->get('content_id');
                $res = db_query($sql);
                $done = true;
            }
        }
        return $done;
    }
    function destroy($id) {
        $sql = 'DELETE FROM widget_wikipage WHERE id = '. db_ei($id) .' AND owner_id = '. db_ei($this->owner_id) ." AND owner_type = '". db_es($this->owner_type) ."'";
        db_query($sql);
    }
    function isUnique() {
        return false;
    }
    
    function getCategory() {
        return 'wiki';
    }
}
?>
