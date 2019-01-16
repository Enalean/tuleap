<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\GitLFS\Lock;

use Tuleap\DB\DataAccessObject;

class LockDao extends DataAccessObject
{
    public function create(
        string $lock_path,
        int $lock_owner,
        ?string $reference,
        int $repository_id,
        int $creation_date
    ): int {
        return (int) $this->getDB()->insertReturnId(
            'plugin_gitlfs_lock',
            [
                'lock_path'     => $lock_path,
                'lock_owner'    => $lock_owner,
                'ref'           => $reference,
                'repository_id' => $repository_id,
                'creation_date' => $creation_date
            ]
        );
    }
}
