<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
* Widget_MyBookmarks
*
* Personal bookmarks
*/
class Widget_MyBookmarks extends Widget
{

    public function __construct()
    {
        parent::__construct('mybookmarks');
    }

    public function getTitle()
    {
        return $GLOBALS['Language']->getText('my_index', 'my_bookmarks');
    }

    public function getContent()
    {
        $html_my_bookmarks = '';
        $result = db_query("SELECT bookmark_url, bookmark_title, bookmark_id from user_bookmarks where " .
            "user_id='" . db_ei(UserManager::instance()->getCurrentUser()->getId()) . "' ORDER BY bookmark_title");
        $rows = db_numrows($result);
        if (!$result || $rows < 1) {
            $html_my_bookmarks .= $GLOBALS['Language']->getText('my_index', 'no_bookmark');
            $html_my_bookmarks .= db_error();
        } else {
            $purifier = Codendi_HTMLPurifier::instance();
            $html_my_bookmarks .= '<table class="tlp-table" style="width:100%">';
            for ($i = 0; $i < $rows; $i++) {
                $bookmark_url = $purifier->purify(db_result($result, $i, 'bookmark_url'), CODENDI_PURIFIER_CONVERT_HTML);
                if (my_has_URL_invalid_content($bookmark_url)) {
                    $bookmark_url = '';
                }
                $bookmark_title = $purifier->purify(db_result($result, $i, 'bookmark_title'), CODENDI_PURIFIER_CONVERT_HTML);
                $html_my_bookmarks .= '<TR class="' . util_get_alt_row_color($i) . '"><TD>';
                $html_my_bookmarks .= '<A HREF="' . $bookmark_url . '">' . $bookmark_title . '</A> ';
                $html_my_bookmarks .= '<small><A HREF="/my/bookmark_edit.php?bookmark_id=' . db_result($result, $i, 'bookmark_id') . '">[' . $GLOBALS['Language']->getText('my_index', 'edit_link') . ']</A></SMALL></TD>';
                $html_my_bookmarks .= '<td style="text-align:right"><A HREF="/my/bookmark_delete.php?bookmark_id=' . db_result($result, $i, 'bookmark_id') . '">';
                $html_my_bookmarks .= '<i class=" fa fa-trash-o" title="' . _('Delete') . '"></A></td></tr>';
            }
            $html_my_bookmarks .= '</table>';
        }
        $html_my_bookmarks .= '<div style="text-align:center; font-size:0.8em;"><a href="/my/bookmark_add.php">[' . $GLOBALS['Language']->getText('my_index', 'add_bookmark') . ']</a></div>';
        return $html_my_bookmarks;
    }
    public function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_my_bookmarks', 'description');
    }
}
