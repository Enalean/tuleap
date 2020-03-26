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

namespace Tuleap\OAuth2Server\RefreshToken;

use DateTimeImmutable;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeRevoker;
use Tuleap\OAuth2Server\Scope\OAuth2ScopeRetriever;

class OAuth2RefreshTokenVerifier
{
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var OAuth2RefreshTokenDAO
     */
    private $refresh_token_dao;
    /**
     * @var OAuth2ScopeRetriever
     */
    private $refresh_token_scope_retriever;
    /**
     * @var OAuth2AuthorizationCodeRevoker
     */
    private $authorization_code_revoker;
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;

    public function __construct(
        SplitTokenVerificationStringHasher $hasher,
        OAuth2RefreshTokenDAO $refresh_token_dao,
        OAuth2ScopeRetriever $refresh_token_scope_retriever,
        OAuth2AuthorizationCodeRevoker $authorization_code_revoker,
        DBTransactionExecutor $db_transaction_executor
    ) {
        $this->hasher                        = $hasher;
        $this->refresh_token_dao             = $refresh_token_dao;
        $this->refresh_token_scope_retriever = $refresh_token_scope_retriever;
        $this->authorization_code_revoker    = $authorization_code_revoker;
        $this->db_transaction_executor       = $db_transaction_executor;
    }

    public function getRefreshToken(OAuth2App $app, SplitToken $refresh_token): OAuth2RefreshToken
    {
        try {
            return $this->verifyRefreshToken($app, $refresh_token);
        } catch (OAuth2RefreshTokenReusedException $exception) {
            // This is done to detect refresh token replay
            // See https://tools.ietf.org/html/draft-ietf-oauth-security-topics-14#section-4.12.2
            $this->authorization_code_revoker->revokeByAuthCodeId($exception->getAuthorizationCodeID());
            throw $exception;
        }
    }

    /**
     * @throws OAuth2RefreshTokenNotFoundException
     * @throws InvalidOAuth2RefreshTokenException
     * @throws OAuth2RefreshTokenReusedException
     * @throws OAuth2RefreshTokenExpiredException
     * @throws OAuth2RefreshTokenNoValidScopeFound
     */
    private function verifyRefreshToken(OAuth2App $app, SplitToken $refresh_token): OAuth2RefreshToken
    {
        return $this->db_transaction_executor->execute(
            function () use ($app, $refresh_token): OAuth2RefreshToken {
                $row = $this->refresh_token_dao->searchRefreshTokenByID($refresh_token->getID());
                if ($row === null) {
                    throw new OAuth2RefreshTokenNotFoundException($refresh_token->getID());
                }

                $is_valid_refresh_token = $this->hasher->verifyHash($refresh_token->getVerificationString(), $row['verifier']);
                if (! $is_valid_refresh_token) {
                    throw new InvalidOAuth2RefreshTokenException();
                }

                if ($row['has_already_been_used']) {
                    throw new OAuth2RefreshTokenReusedException($refresh_token, $row['authorization_code_id']);
                }

                if ($this->isRefreshTokenExpired($row['expiration_date'])) {
                    throw new OAuth2RefreshTokenExpiredException($refresh_token);
                }

                $this->refresh_token_dao->markRefreshTokenAsUsed($refresh_token->getID());

                if ($app->getId() !== $row['app_id']) {
                    throw new OAuth2RefreshTokenDoesNotCorrespondToExpectedAppException(
                        $refresh_token,
                        $app,
                        $row['app_id']
                    );
                }

                $scopes = $this->refresh_token_scope_retriever->getScopesBySplitToken($refresh_token);
                if (empty($scopes)) {
                    throw new OAuth2RefreshTokenNoValidScopeFound($refresh_token);
                }

                return OAuth2RefreshToken::createWithASetOfScopes($row['authorization_code_id'], $scopes);
            }
        );
    }

    private function isRefreshTokenExpired(int $expiration_timestamp): bool
    {
        $current_time = new DateTimeImmutable();

        return $expiration_timestamp <= $current_time->getTimestamp();
    }
}
