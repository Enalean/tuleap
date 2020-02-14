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

use Tuleap\Authentication\Scope\AuthenticationScope;

class AccessKeyScopeSaver
{
    /**
     * @var AccessKeyScopeDAO
     */
    private $dao;

    public function __construct(AccessKeyScopeDAO $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @throws NoValidAccessKeyScopeException
     */
    public function saveKeyScopes(int $access_key_id, AuthenticationScope ...$scopes): void
    {
        $scope_keys = [];

        foreach ($scopes as $scope) {
            $scope_keys[$scope->getIdentifier()->toString()] = true;
        }

        if (empty($scope_keys)) {
            throw new NoValidAccessKeyScopeException($access_key_id);
        }

        $this->dao->saveScopeKeysByAccessKeyID($access_key_id, ...array_keys($scope_keys));
    }
}
