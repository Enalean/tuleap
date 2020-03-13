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

declare(strict_types=1);

namespace Tuleap\User\AccessKey\Scope;

use Tuleap\DB\DataAccessObject;

class AccessKeyScopeDAO extends DataAccessObject
{
    public function saveScopeKeysByAccessKeyID(int $access_key_id, string ...$scope_keys): void
    {
        $data_to_insert = [];

        foreach ($scope_keys as $scope_key) {
            $data_to_insert[] = ['access_key_id' => $access_key_id, 'scope_key' => $scope_key];
        }

        $this->getDB()->insertMany('user_access_key_scope', $data_to_insert);
    }

    /**
     * @psalm-return array<array{scope_key: string}>
     */
    public function searchScopeKeysByAccessKeyID(int $access_key_id): array
    {
        return $this->getDB()->run(
            'SELECT scope_key FROM user_access_key_scope WHERE access_key_id = ?',
            $access_key_id
        );
    }
}
