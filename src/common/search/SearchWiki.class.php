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

class Search_SearchWiki {
    const NAME = 'wiki';

    public function search($group_id, $words) {
        //Wiki language extraction
        $search_page = 'FullTextSearch';

        $group_id = db_ei($group_id);
        $sql = "SELECT DISTINCT wiki_group_list.language_id
                FROM wiki_group_list
                WHERE wiki_group_list.group_id=$group_id
                  AND wiki_group_list.language_id <> '0'";

        $result = db_query($sql);
        if (db_numrows($result)) {
            $row = db_fetch_array($result);
            if ($row['language_id'] == 'fr_FR') {
                $search_page = 'RechercheEnTexteIntégral';
            }
        }

        $GLOBALS['Response']->redirect('/wiki/index.php?group_id=' . $group_id . '&pagename=' . $search_page . '&s=' . urlencode($words));
    }
}
