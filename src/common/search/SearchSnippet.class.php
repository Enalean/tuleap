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

class Search_SearchSnippet {
    const NAME = 'snippets';

    public function search($words, $crit, $offset) {
        /*
          If multiple words, separate them and put LIKE in between
         */
        $array = explode(" ", $words);
        $words1 = implode($array, "%' $crit name LIKE '%");
        $words2 = implode($array, "%' $crit description LIKE '%");

        /*
          Query to find software
         */
        $sql = "SELECT name,snippet_id,description " .
                "FROM snippet " .
                "WHERE ((name LIKE '%$words1%') OR (description LIKE '%$words2%')) LIMIT $offset,26";
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
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'snippet_name');
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'description');

            echo html_build_list_table_top($title_arr);

            echo "\n";

            for ($i = 0; $i < $rows; $i++) {
                print "<TR class=\"" . html_get_alt_row_color($i) . "\"><TD><A HREF=\"/snippet/detail.php?type=snippet&id=" . db_result($result, $i, 'snippet_id') . "\">"
                        . "<IMG SRC=\"" . util_get_image_theme('msg.png') . "\" BORDER=0 HEIGHT=12 WIDTH=10> " . db_result($result, $i, 'name') . "</A></TD>"
                        . "<TD>" . db_result($result, $i, 'description') . "</TD></TR>\n";
            }
            echo "</TABLE>\n";
        }
    }
}