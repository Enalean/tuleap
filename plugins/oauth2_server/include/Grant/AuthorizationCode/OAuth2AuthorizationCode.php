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
use Tuleap\Authentication\SplitToken\SplitToken;

final class OAuth2AuthorizationCode
{
    /**
     * @var int
     * @psalm-readonly
     */
    private $authorization_code_id;
    /**
     * @var AuthenticationScope[]
     *
     * @psalm-var non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>>
     * @psalm-readonly
     */
    private $scopes;
    /**
     * @var PFUser
     * @psalm-readonly
     */
    private $user;
    /**
     * @var string|null
     * @psalm-readonly
     */
    private $pkce_code_challenge;
    /**
     * @var string|null
     * @psalm-readonly
     */
    private $oidc_nonce;

    /**
     * @param AuthenticationScope[] $scopes
     *
     * @psalm-param non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    private function __construct(int $authorization_code_id, PFUser $user, ?string $pkce_code_challenge, ?string $oidc_nonce, array $scopes)
    {
        $this->authorization_code_id = $authorization_code_id;
        $this->user                  = $user;
        $this->pkce_code_challenge   = $pkce_code_challenge;
        $this->oidc_nonce            = $oidc_nonce;
        $this->scopes                = $scopes;
    }

    /**
     * @param AuthenticationScope[] $scopes
     *
     * @psalm-param non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    public static function approveForSetOfScopes(SplitToken $auth_code_token, PFUser $user, ?string $pkce_code_challenge, ?string $oidc_nonce, array $scopes): self
    {
        return new self(
            $auth_code_token->getID(),
            $user,
            $pkce_code_challenge,
            $oidc_nonce,
            $scopes
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function getID(): int
    {
        return $this->authorization_code_id;
    }

    /**
     * @psalm-mutation-free
     */
    public function getUser(): PFUser
    {
        return $this->user;
    }

    /**
     * @return AuthenticationScope[]
     *
     * @psalm-return non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>>
     * @psalm-mutation-free
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @psalm-mutation-free
     */
    public function getPKCECodeChallenge(): ?string
    {
        return $this->pkce_code_challenge;
    }

    /**
     * @psalm-mutation-free
     */
    public function getOIDCNonce(): ?string
    {
        return $this->oidc_nonce;
    }
}
