<?php

require_once('Widget.class.php');

/**
* Widget_Rss
* 
* Rss reader
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
/* abstract */ class Widget_Rss extends Widget {
    var $rss_title;
    var $rss_url;
    function Widget_Rss($id, $owner_id, $owner_type) {
        $this->Widget($id);
        $this->setOwner($owner_id, $owner_type);
    }
    function getTitle() {
        return $this->rss_title ? $this->rss_title : 'RSS Reader';
    }
    function getContent() {
        $content = '';
        if ($this->rss_url) {
            require_once('common/rss/libs/SimplePie/simplepie.inc');
            if (!is_dir($GLOBALS['codex_cache_dir'] .'/rss')) {
                mkdir($GLOBALS['codex_cache_dir'] .'/rss');
            }
            $rss =& new SimplePie($this->rss_url, $GLOBALS['codex_cache_dir'] .'/rss', null, $GLOBALS['sys_proxy']);
            $rss->set_output_encoding('ISO-8859-1');
            $max_items = 10;
            $items = array_slice($rss->get_items(), 0, $max_items);
            $content .= '<table width="100%">';
            $i = 0;
            foreach($items as $item) {
                $content .= '<tr class="'. util_get_alt_row_color($i++) .'"><td WIDTH="99%">';
                $content .= '<a href="'. $item->get_link() .'">'. $item->get_title() .'</a>';
                $content .= '</td></tr>';
            }
            $content .= '</table>';
        }
        return $content;
    }
    function getPreferences() {
        $prefs  = '';
        $prefs .= '<table><tr><td>Title:</td><td><input type="text" class="textfield_medium" name="rss[title]" value="'. htmlentities($this->rss_title, ENT_QUOTES) .'" /></td></tr>';
        $prefs .= '<tr><td>Url:</td><td><input type="text" class="textfield_medium" name="rss[url]" value="'. htmlentities($this->rss_url, ENT_QUOTES) .'" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    function getInstallPreferences() {
        $prefs  = '';
        $prefs .= '<table>';
        $prefs .= '<tr><td>Url:</td><td><input type="text" class="textfield_medium" name="rss[url]" value="http://www.zdnet.fr/feeds/rss/actualites/informatique/?l=5" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    function cloneContent($id, $owner_id, $owner_type) {
        $sql = "INSERT INTO widget_rss (owner_id, owner_type, title, url) 
        SELECT  ". $owner_id .", '". $owner_type ."', title, url
        FROM widget_rss
        WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' ";
        $res = db_query($sql);
        return db_insertid($res);
    }
    function loadContent($id) {
        $sql = "SELECT * FROM widget_rss WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". $id;
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data = db_fetch_array($res);
            $this->rss_title = $data['title'];
            $this->rss_url   = $data['url'];
            $this->content_id = $id;
        }
    }
    function create(&$request) {
        $content_id = false;
        $rss = $request->get('rss');
        if (!isset($rss['url'])) {
            $GLOBALS['Response']->addFeedback('error', "Can't add empty rss url");
        } else {
            if (!isset($rss['title'])) {
                require_once('common/rss/libs/SimplePie/simplepie.inc');
                if (!is_dir($GLOBALS['codex_cache_dir'] .'/rss')) {
                    mkdir($GLOBALS['codex_cache_dir'] .'/rss');
                }
                $rss_reader =& new SimplePie($rss['url'], $GLOBALS['codex_cache_dir'] .'/rss', null, $GLOBALS['sys_proxy']);
                $rss_reader->set_output_encoding('ISO-8859-1');
                $rss['title'] = $rss_reader->get_title();
            }
            $sql = 'INSERT INTO widget_rss (owner_id, owner_type, title, url) VALUES ('. $this->owner_id .", '". $this->owner_type ."', '". db_escape_string($rss['title']) ."', '". db_escape_string($rss['url']) ."')";
            $res = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }
    function updatePreferences(&$request) {
        $done = false;
        if (($rss = $request->get('rss')) && $request->exist('content_id')) {
            $title = isset($rss['title']) ? " title = '". db_escape_string($rss['title']) ."' " : '';
            $url   = isset($rss['url'])   ? " url   = '". db_escape_string($rss['url'])   ."' " : '';
            if ($url || $title) {
                $sql = "UPDATE widget_rss SET ". $title .", ". $url ." WHERE owner_id = ". $this->owner_id ." AND owner_type = '". $this->owner_type ."' AND id = ". (int)$request->get('content_id');
                $res = db_query($sql);
                $done = true;
            }
        }
        return $done;
    }
    function destroy($id) {
        $sql = 'DELETE FROM widget_rss WHERE id = '. $id .' AND owner_id = '. $this->owner_id ." AND owner_type = '". $this->owner_type ."'";
        db_query($sql);
    }
    function isUnique() {
        return false;
    }
}
?>
