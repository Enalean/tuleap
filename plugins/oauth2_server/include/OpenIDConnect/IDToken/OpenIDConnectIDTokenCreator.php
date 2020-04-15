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

namespace Tuleap\OAuth2Server\OpenIDConnect\IDToken;

use Lcobucci\JWT\Signer\Rsa\Sha256;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\OAuth2Server\App\ClientIdentifier;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCode;
use Tuleap\OAuth2Server\OpenIDConnect\Issuer;
use Tuleap\OAuth2Server\OpenIDConnect\Scope\OAuth2SignInScope;

class OpenIDConnectIDTokenCreator
{
    // See https://openid.net/specs/openid-connect-core-1_0.html#IDToken
    private const CLAIM_AUTH_TIME = 'auth_time';

    /**
     * @var OAuth2SignInScope
     */
    private $sign_in_scope;
    /**
     * @var JWTBuilderFactory
     */
    private $builder_factory;
    /**
     * @var \DateInterval
     */
    private $id_token_expiration_delay;
    /**
     * @var OpenIDConnectSigningKeyFactory
     */
    private $signing_key_factory;
    /**
     * @var Sha256
     */
    private $signer;
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct(
        OAuth2SignInScope $sign_in_scope,
        JWTBuilderFactory $builder_factory,
        \DateInterval $date_interval,
        OpenIDConnectSigningKeyFactory $signing_key_factory,
        Sha256 $signer,
        \UserManager $user_manager
    ) {
        $this->sign_in_scope             = $sign_in_scope;
        $this->builder_factory           = $builder_factory;
        $this->id_token_expiration_delay = $date_interval;
        $this->signing_key_factory       = $signing_key_factory;
        $this->signer                    = $signer;
        $this->user_manager              = $user_manager;
    }

    public function issueIDTokenFromAuthorizationCode(\DateTimeImmutable $current_time, OAuth2App $app, OAuth2AuthorizationCode $authorization_code): ?string
    {
        if (! $this->hasNeededScopeToObtainAnIDToken($authorization_code->getScopes())) {
            return null;
        }

        $user        = $authorization_code->getUser();
        $access_info = $this->user_manager->getUserAccessInfo($user);

        $builder = $this->builder_factory->getBuilder()->issuedBy(Issuer::toString())
                ->relatedTo((string) $user->getId())
                ->permittedFor(ClientIdentifier::fromOAuth2App($app)->toString())
                ->issuedAt($current_time->getTimestamp())
                ->expiresAt($current_time->add($this->id_token_expiration_delay)->getTimestamp())
                ->withClaim(self::CLAIM_AUTH_TIME, (int) $access_info['last_auth_success']);

        $nonce = $authorization_code->getOIDCNonce();
        if ($nonce !== null) {
            $builder = $builder->withClaim('nonce', $nonce);
        }

        return (string) $builder->getToken($this->signer, $this->signing_key_factory->getKey());
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
