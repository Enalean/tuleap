<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\Object;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class LFSObjectDAO extends DataAccessObject
{
    public function searchByOIDs(array $oids)
    {
        $oids_in_condition = EasyStatement::open()->in('?*', $oids);

        return $this->getDB()->safeQuery(
            "SELECT * FROM plugin_gitlfs_object WHERE object_oid IN ($oids_in_condition)",
            $oids_in_condition->values()
        );
    }

    /**
     * @return int
     */
    public function saveObject($oid_value, $size)
    {
        return (int) $this->getDB()->insertReturnId(
            'plugin_gitlfs_object',
            [
                'object_oid'  => $oid_value,
                'object_size' => $size
            ]
        );
    }

    public function saveObjectReference($object_id, $repository_id)
    {
        $this->getDB()->insert(
            'plugin_gitlfs_object_repository',
            [
                'object_id'     => $object_id,
                'repository_id' => $repository_id
            ]
        );
    }

    public function saveObjectReferenceByOIDValue($oid_value, $repository_id)
    {
        $sql = 'INSERT IGNORE INTO plugin_gitlfs_object_repository(object_id, repository_id)
                SELECT plugin_gitlfs_object.id, ?
                FROM plugin_gitlfs_object
                WHERE plugin_gitlfs_object.object_oid = ?';
        $this->getDB()->run($sql, $repository_id, $oid_value);
    }
}
