<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
/**
 * Alias for OrphanedPages. Idea and name from mediawiki.
 *
    "SELECT cur_title " .
      "FROM cur LEFT JOIN links ON cur_title = l_from " .
      "WHERE l_from IS NULL " .
      "AND cur_namespace = 0 " .
      "ORDER BY cur_title " .
      "LIMIT {$offset}, {$limit}";
 *
 **/
require_once('lib/PageList.php');
require_once('lib/plugin/OrphanedPages.php');

class WikiPlugin_DeadEndPages extends WikiPlugin_OrphanedPages
{
    public function getName()
    {
        return _("DeadEndPages");
    }
}

// $Log: DeadEndPages.php,v $
// Revision 1.1  2004/05/27 12:10:31  rurban
// The mediawiki name for OrphanedPages. Just an alias.
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
