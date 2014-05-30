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

    /**
     * @var ProjectDao
     */
    private $dao;


    public function __construct(ProjectDao $dao) {
        $this->dao = $dao;
    }

    public function search($words, $exact, $offset) {
        $hp   = Codendi_HTMLPurifier::instance();
        $user = UserManager::instance()->getCurrentUser();
        if ($user->isRestricted()) {
            $results = $this->dao->searchGlobalForRestrictedUsers($words, $offset, $exact, $user->getId());
        } else {
            $results = $this->dao->searchGlobal($words, $offset, $exact);
        }

        $rows_returned = count($results);
        if ($rows_returned == 0) {
            echo '<H2>' . $GLOBALS['Language']->getText('search_index', 'no_match_found', htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8')) . '</H2>';
        } else {

            echo '<H3>' . $GLOBALS['Language']->getText('search_index', 'search_res', array(htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8'), $rows_returned)) . "</H3><P>\n\n";

            $title_arr = array();
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'project_name');
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'description');

            echo html_build_list_table_top($title_arr);

            echo "\n";

            $i = 0;
            foreach ($results as $row) {
                print "<TR class=\"" . html_get_alt_row_color($i) . "\"><TD><A HREF=\"/projects/" . $row['unix_group_name'] . "/\">"
                        . "<IMG SRC=\"" . util_get_image_theme('msg.png') . "\" BORDER=0 HEIGHT=12 WIDTH=10> " . $row['group_name'] . "</A></TD>"
                        . "<TD>" . $hp->purify(util_unconvert_htmlspecialchars($row['short_description' ]), CODENDI_PURIFIER_LIGHT) . "</TD></TR>\n";
                $i++;
            }
            echo "</TABLE>\n";
        }
    }
}
