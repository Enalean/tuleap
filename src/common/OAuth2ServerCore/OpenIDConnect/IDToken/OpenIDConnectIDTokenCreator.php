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

namespace Tuleap\OAuth2ServerCore\OpenIDConnect\IDToken;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCode;
use Tuleap\OAuth2ServerCore\OpenIDConnect\OpenIDConnectTokenBuilder;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OAuth2SignInScope;

class OpenIDConnectIDTokenCreator
{
    // See https://openid.net/specs/openid-connect-core-1_0.html#IDToken
    private const CLAIM_AUTH_TIME = 'auth_time';

    public function __construct(
        private OAuth2SignInScope $sign_in_scope,
        private OpenIDConnectTokenBuilder $token_builder,
        private \UserManager $user_manager,
    ) {
    }

    public function issueIDTokenFromAuthorizationCode(\DateTimeImmutable $current_time, OAuth2App $app, OAuth2AuthorizationCode $authorization_code): ?string
    {
        if (! $this->hasNeededScopeToObtainAnIDToken($authorization_code->getScopes())) {
            return null;
        }

        $user        = $authorization_code->getUser();
        $access_info = $this->user_manager->getUserAccessInfo($user);

        $claims = [self::CLAIM_AUTH_TIME => (int) $access_info['last_auth_success']];
        $nonce  = $authorization_code->getOIDCNonce();
        if ($nonce !== null) {
            $claims['nonce'] = $nonce;
        }

        return $this->token_builder->getToken($current_time, $app, $user, $claims);
    }

    /**
     * @param AuthenticationScope[] $scopes
     *
     * @psalm-param array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    private function hasNeededScopeToObtainAnIDToken(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if ($this->sign_in_scope->covers($scope)) {
                return true;
            }
        }

        return false;
    }
}
