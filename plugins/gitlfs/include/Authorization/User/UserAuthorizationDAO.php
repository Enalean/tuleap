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
 *
 */

namespace Tuleap\GitLFS\Authorization\User;

use Tuleap\DB\DataAccessObject;

class UserAuthorizationDAO extends DataAccessObject
{
    /**
     * @return int
     */
    public function create($repository_id, $hashed_verification_string, $expiration_date, $operation_name, $user_id)
    {
        return (int) $this->getDB()->insertReturnId(
            'plugin_gitlfs_ssh_authorization',
            [
                'repository_id'   => $repository_id,
                'verifier'        => $hashed_verification_string,
                'expiration_date' => $expiration_date,
                'operation_name'  => $operation_name,
                'user_id'         => $user_id,
            ]
        );
    }

    /**
     * @return array|null
     */
    public function searchAuthorizationByIDAndExpiration($id, $repository_id, $current_time)
    {
        return $this->getDB()->row(
            'SELECT * FROM plugin_gitlfs_ssh_authorization WHERE id = ? AND repository_id = ? AND expiration_date >= ?',
            $id,
            $repository_id,
            $current_time
        );
    }

    public function deleteByID($authorization_id)
    {
        $this->getDB()->run('DELETE FROM plugin_gitlfs_ssh_authorization WHERE id = ?', $authorization_id);
    }

    public function deleteByExpirationDate($expiration_date)
    {
        $this->getDB()->run('DELETE FROM plugin_gitlfs_ssh_authorization WHERE ? >= expiration_date', $expiration_date);
    }
}
