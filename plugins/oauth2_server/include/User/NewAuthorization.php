<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\User;

use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;

/**
 * @psalm-immutable
 */
final class NewAuthorization
{
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var int
     */
    private $app_id;
    /**
     * @var AuthenticationScopeIdentifier[]
     */
    private $scope_identifiers;

    public function __construct(\PFUser $user, int $app_id, AuthenticationScopeIdentifier ...$scope_identifiers)
    {
        $this->user              = $user;
        $this->app_id            = $app_id;
        $this->scope_identifiers = $scope_identifiers;
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }

    public function getAppId(): int
    {
        return $this->app_id;
    }

    /**
     * @return AuthenticationScopeIdentifier[]
     */
    public function getScopeIdentifiers(): array
    {
        return $this->scope_identifiers;
    }
}
