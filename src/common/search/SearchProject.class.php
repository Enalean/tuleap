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

class Search_SearchProject {
    const NAME = 'soft';

    public function search($words, $crit, $offset) {
        $hp = Codendi_HTMLPurifier::instance();
        /*
          If multiple words, separate them and put LIKE in between
         */
        $array = explode(" ", $words);
        $words1 = implode($array, "%' $crit group_name LIKE '%");
        $words2 = implode($array, "%' $crit short_description LIKE '%");
        $words3 = implode($array, "%' $crit unix_group_name LIKE '%");

        $user = UserManager::instance()->getCurrentUser();
        if ($user->isRestricted()) {
            $from_restricted = " JOIN user_group ON (user_group.group_id = groups.group_id)";
            $where_restricted = " AND user_group.user_id = '" . $user->getID() . "'";
        } else {
            $from_restricted = "";
            $where_restricted = "";
        }

        /*
          Query to find software
         */
        $sql = "SELECT DISTINCT group_name, unix_group_name, groups.group_id, short_description
            FROM groups
                LEFT JOIN group_desc_value ON (group_desc_value.group_id = groups.group_id)
                $from_restricted
            WHERE status='A'
            AND is_public='1'
            AND (
                    (group_name LIKE '%$words1%')
                 OR (short_description LIKE '%$words2%')
                 OR (unix_group_name LIKE '%$words3%')
                 OR (group_desc_value.value LIKE '%$words3%')
            )
            $where_restricted
            LIMIT $offset,26";
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
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'project_name');
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'description');

            echo html_build_list_table_top($title_arr);

            echo "\n";

            for ($i = 0; $i < $rows; $i++) {
                print "<TR class=\"" . html_get_alt_row_color($i) . "\"><TD><A HREF=\"/projects/" . db_result($result, $i, 'unix_group_name') . "/\">"
                        . "<IMG SRC=\"" . util_get_image_theme('msg.png') . "\" BORDER=0 HEIGHT=12 WIDTH=10> " . db_result($result, $i, 'group_name') . "</A></TD>"
                        . "<TD>" . $hp->purify(util_unconvert_htmlspecialchars(db_result($result, $i, 'short_description')), CODENDI_PURIFIER_LIGHT) . "</TD></TR>\n";
            }
            echo "</TABLE>\n";
        }
    }
}
