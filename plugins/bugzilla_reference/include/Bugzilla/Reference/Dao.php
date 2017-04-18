<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Bugzilla\Reference;

class Dao extends \DataAccessObject
{
    public function save($keyword, $server, $username, $password, $are_followups_private)
    {
        $keyword               = $this->da->quoteSmart($keyword);
        $server                = $this->da->quoteSmart($server);
        $username              = $this->da->quoteSmart($username);
        $password              = $this->da->quoteSmart($password);
        $are_followups_private = $this->da->escapeInt($are_followups_private);

        $sql_save = "INSERT INTO plugin_bugzilla_reference(keyword, server, username, password, are_followup_private)
                      VALUES ($keyword, $server, $username, $password, $are_followups_private)";

        return $this->update($sql_save);
    }

    public function searchAllReferences()
    {
        $sql = "SELECT * FROM plugin_bugzilla_reference";

        return $this->retrieve($sql);
    }

    public function searchReferenceByKeyword($keyword)
    {
        $keyword = $this->da->quoteSmart($keyword);
        $sql     = "SELECT * FROM plugin_bugzilla_reference WHERE keyword = $keyword";

        return $this->retrieveFirstRow($sql);
    }
}
