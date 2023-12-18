<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class SvnCommitsDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'svn_commits';
    }

    public function updateCommitMessage($group_id, $revision, $description)
    {
        $group_id    = $this->da->escapeInt($group_id);
        $revision    = $this->da->escapeInt($revision);
        $description = $this->da->quoteSmart($description);
        $sql         = "UPDATE svn_commits
                SET description = $description
                WHERE group_id = $group_id AND revision=$revision";
        $this->update($sql);
    }
}
