<?php

require_once('Widget.class.php');

/**
* Widget_MyRss
* 
* Personal bookmarks
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_MyRss extends Widget {
    var $myrss_title;
    var $myrss_url;
    function Widget_MyRss() {
        $this->Widget('myrss');
    }
    function getTitle() {
        return $this->myrss_title ? $this->myrss_title : 'RSS Reader';
    }
    function getContent() {
        $content = '';
        if ($this->myrss_url) {
            require_once('common/rss/libs/SimplePie/simplepie.inc');
            if (!is_dir($GLOBALS['codex_cache_dir'] .'/rss')) {
                mkdir($GLOBALS['codex_cache_dir'] .'/rss');
            }
            $rss =& new SimplePie($this->myrss_url, $GLOBALS['codex_cache_dir'] .'/rss', null, $GLOBALS['sys_proxy']);
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
        $prefs .= '<table><tr><td>Title:</td><td><input type="text" class="textfield_medium" name="myrss[title]" value="'. htmlentities($this->myrss_title, ENT_QUOTES) .'" /></td></tr>';
        $prefs .= '<tr><td>Url:</td><td><input type="text" class="textfield_medium" name="myrss[url]" value="'. htmlentities($this->myrss_url, ENT_QUOTES) .'" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    function getInstallPreferences() {
        $prefs  = '';
        $prefs .= '<table>';
        $prefs .= '<tr><td>Url:</td><td><input type="text" class="textfield_medium" name="myrss[url]" value="http://www.zdnet.fr/feeds/rss/actualites/informatique/?l=5" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    
    function loadContent($id) {
        $sql = "SELECT * FROM widget_rss WHERE owner_id = ". user_getid() ." AND owner_type = 'u' id = ". $id;
        $res = db_query($sql);
        if ($res && db_numrows($res)) {
            $data = db_fetch_array($res);
            $this->myrss_title = $data['title'];
            $this->myrss_url   = $data['url'];
            $this->content_id = $id;
        }
    }
    function create(&$request) {
        $content_id = false;
        $myrss = $request->get('myrss');
        if (!isset($myrss['url'])) {
            $GLOBALS['Response']->addFeedback('error', "Can't add empty rss url");
        } else {
            if (!isset($myrss['title'])) {
                require_once('common/rss/libs/SimplePie/simplepie.inc');
                if (!is_dir($GLOBALS['codex_cache_dir'] .'/rss')) {
                    mkdir($GLOBALS['codex_cache_dir'] .'/rss');
                }
                $rss =& new SimplePie($myrss['url'], $GLOBALS['codex_cache_dir'] .'/rss', null, $GLOBALS['sys_proxy']);
                $rss->set_output_encoding('ISO-8859-1');
                new dBug($rss);
                $myrss['title'] = $rss->get_title();
            }
            $sql = 'INSERT INTO widget_rss (owner_id, owner_type, title, url) VALUES ('. user_getid() .", 'u', '". db_escape_string($myrss['title']) ."', '". db_escape_string($myrss['url']) ."')";
            $res = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }
    function updatePreferences(&$request) {
        $done = false;
        if (($myrss = $request->get('myrss')) && $request->exist('content_id')) {
            $title = isset($myrss['title']) ? " title = '". db_escape_string($myrss['title']) ."' " : '';
            $url   = isset($myrss['url'])   ? " url   = '". db_escape_string($myrss['url'])   ."' " : '';
            if ($url || $title) {
                $sql = "UPDATE widget_rss SET ". $title .", ". $url ." WHERE owner_id = ". user_getid() ." AND owner_type = 'u' AND id = ". (int)$request->get('content_id');
                $res = db_query($sql);
                $done = true;
            }
        }
        return $done;
    }
    function destroy($id) {
        $sql = 'DELETE FROM widget_rss WHERE id = '. $id .' AND owner_id = '. user_getid() ." AND owner_type = 'u'";
        db_query($sql);
    }
    function isUnique() {
        return false;
    }
}
?>
