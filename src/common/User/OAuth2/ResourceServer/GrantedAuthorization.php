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

namespace Tuleap\User\OAuth2\ResourceServer;

use Tuleap\Authentication\Scope\AuthenticationScope;

final class GrantedAuthorization
{
    /**
     * @psalm-readonly
     * @var \PFUser
     */
    private $user;

    /**
     * @psalm-readonly
     * @var AuthenticationScope[]
     */
    private $scopes;

    /**
     * @param AuthenticationScope[] $scopes
     * @psalm-param non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    public function __construct(\PFUser $user, array $scopes)
    {
        $this->user   = $user;
        $this->scopes = $scopes;
    }

    /**
     * @psalm-pure
     */
    public function getUser(): \PFUser
    {
        return $this->user;
    }

    /**
     * @psalm-pure
     * @psalm-return AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }
}
