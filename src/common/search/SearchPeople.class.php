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

    /**
     * @var UserDao
     */
    private $dao;


    public function __construct(UserDao $dao) {
        $this->dao = $dao;
    }

    public function search($words, $exact, $offset) {
        $hp = Codendi_HTMLPurifier::instance();

        $results = $this->dao->searchGlobal($words, $offset, $exact);
        $rows_returned = count($results);

        if (! $rows_returned) {
            echo '<H2>' . $GLOBALS['Language']->getText('search_index', 'no_match_found', $hp->purify($words, CODENDI_PURIFIER_CONVERT_HTML)) . '</H2>';
        } else {

            echo '<H3>' . $GLOBALS['Language']->getText('search_index', 'search_res', array(htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8'), $rows_returned)) . "</H3><P>\n\n";

            $title_arr = array();
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'user_n');
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'real_n');

            echo html_build_list_table_top($title_arr);

            echo "\n";

            $i = 0;
            foreach ($results as $row) {
                $i++;
                print "<TR class=\"" . html_get_alt_row_color($i) . "\"><TD><A HREF=\"/users/" . $row['user_name'] . "/\">"
                        . "<IMG SRC=\"" . util_get_image_theme('msg.png') . "\" BORDER=0 HEIGHT=12 WIDTH=10> " . $row['user_name'] . "</A></TD>"
                        . "<TD>" . $hp->purify($row['realname'], CODENDI_PURIFIER_CONVERT_HTML) . "</TD></TR>\n";
            }
            echo "</TABLE>\n";
        }
    }
}
