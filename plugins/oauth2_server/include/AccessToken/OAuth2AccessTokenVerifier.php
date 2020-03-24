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

namespace Tuleap\OAuth2Server\AccessToken;

use DateTimeImmutable;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\OAuth2Server\Scope\OAuth2ScopeRetriever;
use Tuleap\User\OAuth2\AccessToken\InvalidOAuth2AccessTokenException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenDoesNotHaveRequiredScopeException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenExpiredException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenMatchingUnknownUserException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenNotFoundException;

class OAuth2AccessTokenVerifier
{
    /**
     * @var OAuth2AccessTokenDAO
     */
    private $access_token_dao;
    /**
     * @var OAuth2ScopeRetriever
     */
    private $scope_retriever;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;

    public function __construct(
        OAuth2AccessTokenDAO $access_token_dao,
        OAuth2ScopeRetriever $scope_retriever,
        \UserManager $user_manager,
        SplitTokenVerificationStringHasher $hasher
    ) {
        $this->access_token_dao = $access_token_dao;
        $this->scope_retriever  = $scope_retriever;
        $this->user_manager     = $user_manager;
        $this->hasher           = $hasher;
    }

    /**
     * @psalm-param AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier> $required_scope
     *
     * @throws OAuth2AccessTokenNotFoundException
     * @throws InvalidOAuth2AccessTokenException
     * @throws OAuth2AccessTokenMatchingUnknownUserException
     * @throws OAuth2AccessTokenExpiredException
     * @throws OAuth2AccessTokenDoesNotHaveRequiredScopeException
     */
    public function getUser(SplitToken $access_token, AuthenticationScope $required_scope): \PFUser
    {
        $row = $this->access_token_dao->searchAccessToken($access_token->getID());
        if ($row === null) {
            throw new OAuth2AccessTokenNotFoundException($access_token->getID());
        }

        $is_valid_access_token = $this->hasher->verifyHash($access_token->getVerificationString(), $row['verifier']);
        if (! $is_valid_access_token) {
            throw new InvalidOAuth2AccessTokenException();
        }

        if ($this->isAccessTokenExpired($row['expiration_date'])) {
            throw new OAuth2AccessTokenExpiredException($access_token);
        }

        if (! $this->hasNeededScopes($access_token, $required_scope)) {
            throw new OAuth2AccessTokenDoesNotHaveRequiredScopeException($required_scope);
        }

        $user = $this->user_manager->getUserById($row['user_id']);
        if ($user === null) {
            throw new OAuth2AccessTokenMatchingUnknownUserException($row['user_id']);
        }
        return $user;
    }

    private function isAccessTokenExpired(int $expiration_timestamp): bool
    {
        $current_time = new DateTimeImmutable();

        return $expiration_timestamp <= $current_time->getTimestamp();
    }

    /**
     * @psalm-param AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier> $required_scope
     */
    private function hasNeededScopes(SplitToken $access_token, AuthenticationScope $required_scope): bool
    {
        $access_token_scopes = $this->scope_retriever->getScopesBySplitToken($access_token);
        foreach ($access_token_scopes as $access_token_scope) {
            if ($access_token_scope->covers($required_scope)) {
                return true;
            }
        }
        return false;
    }
}
