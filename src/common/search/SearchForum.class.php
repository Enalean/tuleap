<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Search_SearchForum {
    const NAME = 'forums';

    public function search($words, $crit, $offset, $forum_id) {
        $array = explode(" ", $words);
        $words1 = implode($array, "%' $crit forum.body LIKE '%");
        $words2 = implode($array, "%' $crit forum.subject LIKE '%");
        $forum_id = db_ei($forum_id);

        $sql = "SELECT forum.msg_id,forum.subject,forum.date,user.user_name "
                . "FROM forum,user "
                . "WHERE user.user_id=forum.posted_by AND ((forum.body LIKE '%$words1%') "
                . "OR (forum.subject LIKE '%$words2%')) AND forum.group_forum_id=$forum_id "
                . "GROUP BY msg_id,subject,date,user_name LIMIT $offset,26";
        $result = db_query($sql);
        $rows = $rows_returned = db_numrows($result);

        if (!$result || $rows < 1) {
            $no_rows = 1;
            echo '<H2>' . $GLOBALS['Language']->getText('search_index', 'no_match_found', htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8')) . '</H2>';
            echo db_error();
//		echo $sql;
        } else {

            if ($rows_returned > 25) {
                $rows = 25;
            }

            echo '<H3>' . $GLOBALS['Language']->getText('search_index', 'search_res', array(htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8'), $rows_returned)) . "</H3><P>\n\n";

            $title_arr = array();
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'thread');
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'author');
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'date');

            echo html_build_list_table_top($title_arr);

            echo "\n";

            for ($i = 0; $i < $rows; $i++) {
                print "<TR class=\"" . html_get_alt_row_color($i) . "\"><TD><A HREF=\"/forum/message.php?msg_id="
                        . db_result($result, $i, "msg_id") . "\"><IMG SRC=\"" . util_get_image_theme('msg.png') . "\" BORDER=0 HEIGHT=12 WIDTH=10> "
                        . db_result($result, $i, "subject") . "</A></TD>"
                        . "<TD>" . db_result($result, $i, "user_name") . "</TD>"
                        . "<TD>" . format_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, $i, "date")) . "</TD></TR>\n";
            }
            echo "</TABLE>\n";
        }
    }
}