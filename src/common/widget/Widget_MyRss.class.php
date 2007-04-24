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
    function Widget_MyRss() {
        $this->Widget('myrss');
    }
    function getTitle() {
        return 'RSS Reader';
    }
    function getContent() {
        $content = '';
        require_once('common/rss/libs/magpierss/rss_fetch.inc');
        $rss = fetch_rss('http://www.zdnet.fr/feeds/rss/actualites/informatique/?l=5');
        $max_items = 10;
        $items = array_slice($rss->items, 0, $max_items);
        $content .= '<table>';
        $i = 0;
        foreach($items as $item) {
            $content .= '<tr class="'. util_get_alt_row_color($i++) .'"><td WIDTH="99%">';
            $content .= '<a href="'. $item['link'] .'">'. $item['title'] .'</a>';
            $content .= '</td></tr>';
        }
        $content .= '</table>';
        return $content;
    }
    function getPreferences() {
        $prefs  = '';
        $prefs .= '<table><tr><td>Title:</td><td><input type="text" class="textfield_medium" name="myrss[title]" value="ZDNet News" /></td></tr>';
        $prefs .= '<tr><td>Url:</td><td><input type="text" class="textfield_medium" name="myrss[url]" value="http://www.zdnet.fr/feeds/rss/actualites/informatique/?l=5" /></td></tr>';
        $prefs .= '</table>';
        return $prefs;
    }
    function getInstallPreferences() {
        return $this->getPreferences();
    }
    function create(&$request) {
        $content_id = false;
        $myrss = $request->get('myrss');
        if (!isset($myrss['title'])) {
            $myrss['title'] = '';
        }
        if (!isset($myrss['url'])) {
            $GLOBALS['Response']->addFeedback('error', "Can't add empty rss url");
        } else {
            $sql = 'INSERT INTO user_rss (user_id, title, url) VALUES ('. user_getid() .", '". $myrss['title'] ."', '". $myrss['url'] ."')";
            $res = db_query($sql);
            $content_id = db_insertid($res);
        }
        return $content_id;
    }
    function isUnique() {
        return false;
    }
}
?>
