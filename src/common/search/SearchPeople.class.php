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

class Search_SearchPeople {
    const NAME = 'people';

    public function search($words, $crit, $offset) {
        $hp = Codendi_HTMLPurifier::instance();
        /*
          If multiple words, separate them and put LIKE in between
         */
        $array = explode(" ", $words);
        $words1 = implode($array, "%' $crit user_name LIKE '%");
        $words2 = implode($array, "%' $crit realname LIKE '%");

        /*
          Query to find users
         */
        $sql = "SELECT user_name,user_id,realname "
                . "FROM user "
                . "WHERE ((user_name LIKE '%$words1%') OR (realname LIKE '%$words2%')) AND ((status='A') OR (status='R')) ORDER BY user_name LIMIT $offset,26";
        $result = db_query($sql);
        $rows = $rows_returned = db_numrows($result);

        if (!$result || $rows < 1) {
            $no_rows = 1;
            echo '<H2>' . $GLOBALS['Language']->getText('search_index', 'no_match_found', $hp->purify($words, CODENDI_PURIFIER_CONVERT_HTML)) . '</H2>';
            echo db_error();
//		echo $sql;
        } else {

            if ($rows_returned > 25) {
                $rows = 25;
            }

            echo '<H3>' . $GLOBALS['Language']->getText('search_index', 'search_res', array(htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8'), $rows_returned)) . "</H3><P>\n\n";

            $title_arr = array();
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'user_n');
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'real_n');

            echo html_build_list_table_top($title_arr);

            echo "\n";

            for ($i = 0; $i < $rows; $i++) {
                print "<TR class=\"" . html_get_alt_row_color($i) . "\"><TD><A HREF=\"/users/" . db_result($result, $i, 'user_name') . "/\">"
                        . "<IMG SRC=\"" . util_get_image_theme('msg.png') . "\" BORDER=0 HEIGHT=12 WIDTH=10> " . db_result($result, $i, 'user_name') . "</A></TD>"
                        . "<TD>" . $hp->purify(db_result($result, $i, 'realname'), CODENDI_PURIFIER_CONVERT_HTML) . "</TD></TR>\n";
            }
            echo "</TABLE>\n";
        }
    }
}
