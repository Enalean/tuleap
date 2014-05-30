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

    /**
     * @var SnippetDao
     */
    private $dao;


    public function __construct(SnippetDao $dao) {
        $this->dao = $dao;
    }

    public function search($words, $exact, $offset) {
        $results = $this->dao->searchGlobal($words, $exact, $offset);
        $rows_returned = count($results);

        if (! $rows_returned) {
            echo '<H2>' . $GLOBALS['Language']->getText('search_index', 'no_match_found', htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8')) . '</H2>';
        } else {
            echo '<H3>' . $GLOBALS['Language']->getText('search_index', 'search_res', array(htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8'), $rows_returned)) . "</H3><P>\n\n";

            $title_arr = array();
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'snippet_name');
            $title_arr[] = $GLOBALS['Language']->getText('search_index', 'description');

            echo html_build_list_table_top($title_arr);

            echo "\n";

            foreach ($results as $row) {
                print "<TR><TD><A HREF=\"/snippet/detail.php?type=snippet&id=" . $row['snippet_id'] . "\">"
                        . "<IMG SRC=\"" . util_get_image_theme('msg.png') . "\" BORDER=0 HEIGHT=12 WIDTH=10> " . $row['name'] . "</A></TD>"
                        . "<TD>" . $row['description'] . "</TD></TR>\n";
            }
            echo "</TABLE>\n";
        }
    }
}