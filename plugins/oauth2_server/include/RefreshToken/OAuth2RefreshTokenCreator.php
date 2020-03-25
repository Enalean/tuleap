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

use DateInterval;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCode;
use Tuleap\OAuth2Server\Scope\OAuth2ScopeSaver;

class OAuth2RefreshTokenCreator
{
    /**
     * @var OAuth2OfflineAccessScope
     */
    private $offline_access_scope;
    /**
     * @var SplitTokenFormatter
     */
    private $refresh_token_formatter;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var OAuth2RefreshTokenDAO
     */
    private $dao;
    /**
     * @var OAuth2ScopeSaver
     */
    private $scope_saver;
    /**
     * @var DateInterval
     */
    private $refresh_token_expiration_delay;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        OAuth2OfflineAccessScope $offline_access_scope,
        SplitTokenFormatter $refresh_token_formatter,
        SplitTokenVerificationStringHasher $hasher,
        OAuth2RefreshTokenDAO $dao,
        OAuth2ScopeSaver $scope_saver,
        DateInterval $refresh_token_expiration_delay,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->offline_access_scope           = $offline_access_scope;
        $this->refresh_token_formatter        = $refresh_token_formatter;
        $this->hasher                         = $hasher;
        $this->dao                            = $dao;
        $this->scope_saver                    = $scope_saver;
        $this->refresh_token_expiration_delay = $refresh_token_expiration_delay;
        $this->transaction_executor           = $transaction_executor;
    }


    public function issueRefreshTokenIdentifierFromAuthorizationCode(\DateTimeImmutable $current_time, OAuth2AuthorizationCode $authorization_code): ?ConcealedString
    {
        return $this->issueRefreshTokenIdentifier($current_time, $authorization_code->getID(), $authorization_code->getScopes());
    }

    public function issueRefreshTokenIdentifierFromExistingRefreshToken(\DateTimeImmutable $current_time, OAuth2RefreshToken $refresh_token): ?ConcealedString
    {
        return $this->issueRefreshTokenIdentifier($current_time, $refresh_token->getAssociatedAuthorizationCodeID(), $refresh_token->getScopes());
    }

    /**
     * @param AuthenticationScope[] $scopes
     *
     * @psalm-param non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    private function issueRefreshTokenIdentifier(\DateTimeImmutable $current_time, int $authorization_code_id, array $scopes): ?ConcealedString
    {
        if (! $this->hasNeededScopeToObtainARefreshToken($scopes)) {
            return null;
        }

        $verification_string = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $expiration_date     = $current_time->add($this->refresh_token_expiration_delay);

        $refresh_token_id = $this->transaction_executor->execute(
            function () use ($verification_string, $expiration_date, $authorization_code_id, $scopes) : int {
                $refresh_token_id = $this->dao->create(
                    $authorization_code_id,
                    $this->hasher->computeHash($verification_string),
                    $expiration_date->getTimestamp()
                );
                $this->scope_saver->saveScopes($refresh_token_id, $scopes);

                return $refresh_token_id;
            }
        );

        return $this->refresh_token_formatter->getIdentifier(
            new SplitToken($refresh_token_id, $verification_string)
        );
    }

    /**
     * @param AuthenticationScope[] $scopes
     *
     * @psalm-param array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    private function hasNeededScopeToObtainARefreshToken(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if ($this->offline_access_scope->covers($scope)) {
                return true;
            }
        }

        return false;
    }
}
