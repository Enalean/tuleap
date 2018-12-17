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

namespace Tuleap\GitLFS\Authorization\Action;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationType;

class ActionAuthorizationDAO extends DataAccessObject
{
    /**
     * @return int
     */
    public function create($repository_id, $hashed_verification_string, $expiration_date, $action_type, $oid, $size)
    {
        return (int) $this->getDB()->insertReturnId(
            'plugin_gitlfs_authorization_action',
            [
                'repository_id'   => $repository_id,
                'verifier'        => $hashed_verification_string,
                'expiration_date' => $expiration_date,
                'action_type'     => $action_type,
                'object_oid'      => $oid,
                'object_size'     => $size
            ]
        );
    }

    public function searchExistingOIDsForAuthorizedActionByExpirationAndOIDs($current_time, array $oids)
    {
        if (empty($oids)) {
            return [];
        }
        $condition = EasyStatement::open()->with('expiration_date >= ?', $current_time)->andIn('object_oid IN (?*)', $oids);
        return $this->getDB()->column(
            "SELECT DISTINCT object_oid
            FROM plugin_gitlfs_authorization_action
            WHERE $condition",
            $condition->values()
        );
    }

    /**
     * @return array|null
     */
    public function searchAuthorizationByIDAndExpiration($id, $current_time)
    {
        return $this->getDB()->row(
            'SELECT * FROM plugin_gitlfs_authorization_action WHERE id = ? AND expiration_date >= ?',
            $id,
            $current_time
        );
    }

    /**
     * @return array|null
     */
    public function searchAuthorizationTypeWithoutObjectByRepositoriesIdsAndExpiration(ActionAuthorizationType $action, array $repositories_ids, \DateTimeImmutable $current_time)
    {

        $condition = EasyStatement::open()
            ->with('plugin_gitlfs_object.object_oid IS NULL')
            ->andWith('action_type = ?', $action->getName())
            ->andWith('expiration_date >= ?', $current_time->getTimestamp())
            ->andIn('repository_id IN (?*)', $repositories_ids);

        return $this->getDB()->safeQuery(
            "SELECT plugin_gitlfs_authorization_action.*
            FROM plugin_gitlfs_authorization_action
            LEFT JOIN plugin_gitlfs_object
              ON plugin_gitlfs_authorization_action.object_oid = plugin_gitlfs_object.object_oid
            WHERE $condition",
            $condition->values()
        );
    }

    public function deleteByExpirationDate($expiration_date)
    {
        $this->getDB()->run('DELETE FROM plugin_gitlfs_authorization_action WHERE ? >= expiration_date', $expiration_date);
    }
}
