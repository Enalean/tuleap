<?php
/**
 * Copyright (c) Enalean SAS 2014 - Present. All rights reserved
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class SVN_AccessFile_DAO extends DataAccessObject
{
    public function getAllVersions($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);

        $sql = "SELECT version_number, id, version_date, content
                FROM svn_accessfile_history
                WHERE group_id = $group_id";

        return $this->retrieve($sql);
    }

    public function getCurrentVersionNumber($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);

        $sql = "SELECT s.version_number
                FROM svn_accessfile_history s
                    JOIN `groups` AS g ON g.group_id = s.group_id
                WHERE g.group_id = $group_id
                    AND g.svn_accessfile_version_id = s.id";

        $result = $this->retrieve($sql);

        if (! $result) {
            return null;
        }

        $row = $result->getRow();

        return $row['version_number'];
    }
}
