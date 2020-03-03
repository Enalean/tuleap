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

namespace Tuleap\OAuth2Server\Grant\AuthorizationCode;

use PFUser;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\User\OAuth2\Scope\DemoOAuth2Scope;

/**
 * @psalm-immutable
 */
final class OAuth2AuthorizationCode
{
    /**
     * @var AuthenticationScope[]
     *
     * @psalm-var non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>>
     */
    private $scopes;
    /**
     * @var PFUser
     */
    private $user;

    /**
     * @param AuthenticationScope[] $scopes
     *
     * @psalm-param non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    private function __construct(PFUser $user, array $scopes)
    {
        $this->user   = $user;
        $this->scopes = $scopes;
    }

    public static function approveForDemoScope(PFUser $user): self
    {
        return new self(
            $user,
            [DemoOAuth2Scope::fromItself()]
        );
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }

    /**
     * @return AuthenticationScope[]
     *
     * @psalm-return non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }
}
