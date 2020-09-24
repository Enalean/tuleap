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

namespace Tuleap\GitLFS\LFSObject;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class LFSObjectDAO extends DataAccessObject
{
    public function searchByRepositoryIDAndOIDs($repository_id, array $oids)
    {
        if (empty($oids)) {
            return [];
        }
        $condition = EasyStatement::open()->with('repository_id = ?', $repository_id)->andIn('object_oid IN (?*)', $oids);
        return $this->getDB()->safeQuery(
            "SELECT plugin_gitlfs_object.*
            FROM plugin_gitlfs_object
            JOIN plugin_gitlfs_object_repository ON (plugin_gitlfs_object.id = plugin_gitlfs_object_repository.object_id)
            WHERE $condition",
            $condition->values()
        );
    }

    /**
     * @return null|array
     */
    public function searchByOIDValue($oid_value)
    {
        return $this->getDB()->row('SELECT * FROM plugin_gitlfs_object WHERE object_oid = ?', $oid_value);
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

    public function duplicateObjectReferences($to_repository_id, $from_repository_id)
    {
        $sql = 'INSERT IGNORE INTO plugin_gitlfs_object_repository(object_id, repository_id)
                SELECT plugin_gitlfs_object_repository.object_id, ?
                FROM plugin_gitlfs_object_repository
                WHERE plugin_gitlfs_object_repository.repository_id = ?';
        $this->getDB()->run($sql, $to_repository_id, $from_repository_id);
    }

    public function deleteUnusableReferences(int $deletion_delay)
    {
        $sql = '
            DELETE plugin_gitlfs_object_repository.*
            FROM plugin_gitlfs_object_repository
            LEFT JOIN plugin_git ON (plugin_git.repository_id = plugin_gitlfs_object_repository.repository_id)
            LEFT JOIN `groups` ON (`groups`.group_id = plugin_git.project_id)
            WHERE `groups`.status = "D"
               OR plugin_git.repository_id IS NULL
               OR `groups`.group_id IS NULL
               OR (plugin_git.repository_deletion_date <> "0000-00-00 00:00:00" AND TO_DAYS(NOW()) - TO_DAYS(plugin_git.repository_deletion_date) > ?)';
        $this->getDB()->run($sql, $deletion_delay);
    }

    /**
     * @return array
     */
    public function searchUnusedObjects()
    {
        return $this->getDB()->run('
            SELECT plugin_gitlfs_object.*
            FROM plugin_gitlfs_object
            LEFT JOIN plugin_gitlfs_object_repository ON (plugin_gitlfs_object_repository.object_id = plugin_gitlfs_object.id)
            WHERE plugin_gitlfs_object_repository.object_id IS NULL
        ');
    }

    public function deleteObjectByID($id)
    {
        $this->getDB()->delete('plugin_gitlfs_object', ['id' => $id]);
    }
}
