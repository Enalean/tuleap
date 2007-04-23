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
    function _getTitle() {
        return 'ZDNet News';
    }
    function _getContent() {
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
        $prefs .= '<form method="POST" action="widget.php?action=update&amp;name='. $this->id .'">';
        $prefs .= '<fieldset><legend>Preferences</legend>';
        $prefs .= '<table><tr><td>Title:</td><td><input type="text" class="textfield_medium" value="ZDNet News" /></td></tr>';
        $prefs .= '<tr><td>Url:</td><td><input type="text" class="textfield_medium" value="http://www.zdnet.fr/feeds/rss/actualites/informatique/?l=5" /></td></tr>';
        $prefs .= '</table>';
        $prefs .= '<input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'" />&nbsp;';
        $prefs .= '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $prefs .= '</fieldset>';
        $prefs .= '</form>';
        return $prefs;
    }
}
?>
