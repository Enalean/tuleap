<?php

require_once('Widget.class.php');

/**
* Widget_MyBookmarks
* 
* Personal bookmarks
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_MyBookmarks extends Widget {
    function Widget_MyBookmarks() {
        $this->Widget('mybookmarks');
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('my_index', 'my_bookmarks');
    }
    function getContent() {
        $html_my_bookmarks = '';
        $result = db_query("SELECT bookmark_url, bookmark_title, bookmark_id from user_bookmarks where ".
            "user_id='". user_getid() ."' ORDER BY bookmark_title");
        $rows = db_numrows($result);
        if (!$result || $rows < 1) {
            $html_my_bookmarks .= $GLOBALS['Language']->getText('my_index', 'no_bookmark');
            $html_my_bookmarks .= db_error();
        } else {
            $html_my_bookmarks .= '<table style="width:100%">';
            for ($i=0; $i<$rows; $i++) {
                $html_my_bookmarks .= '<TR class="'. util_get_alt_row_color($i) .'"><TD>';
                $html_my_bookmarks .= '<A HREF="'. db_result($result,$i,'bookmark_url') .'">'. db_result($result,$i,'bookmark_title') .'</A> ';
                $html_my_bookmarks .= '<small><A HREF="/my/bookmark_edit.php?bookmark_id='. db_result($result,$i,'bookmark_id') .'">['.$GLOBALS['Language']->getText('my_index', 'edit_link').']</A></SMALL></TD>';
                $html_my_bookmarks .= '<td style="text-align:right"><A HREF="/my/bookmark_delete.php?bookmark_id='. db_result($result,$i,'bookmark_id');
                $html_my_bookmarks .= '" onClick="return confirm(\''.$GLOBALS['Language']->getText('my_index', 'del_bookmark').'\')">';
                $html_my_bookmarks .= '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE"></A></td></tr>';
            }
            $html_my_bookmarks .= '</table>';
        }
        $html_my_bookmarks .= '<div style="text-align:center; font-size:0.8em;"><a href="/my/bookmark_add.php">['. $GLOBALS['Language']->getText('my_index', 'add_bookmark') .']</a></div>';
        return $html_my_bookmarks;
    }
}
?>
