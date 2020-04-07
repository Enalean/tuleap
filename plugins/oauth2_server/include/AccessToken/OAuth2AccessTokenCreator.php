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

use DateInterval;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\OAuth2Server\Scope\OAuth2ScopeSaver;

class OAuth2AccessTokenCreator
{
    /**
     * @var SplitTokenFormatter
     */
    private $access_token_formatter;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var OAuth2AccessTokenDAO
     */
    private $dao;
    /**
     * @var OAuth2ScopeSaver
     */
    private $scope_saver;
    /**
     * @var DateInterval
     */
    private $access_token_expiration_delay;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        SplitTokenFormatter $access_token_formatter,
        SplitTokenVerificationStringHasher $hasher,
        OAuth2AccessTokenDAO $dao,
        OAuth2ScopeSaver $scope_saver,
        DateInterval $access_token_expiration_delay,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->access_token_formatter        = $access_token_formatter;
        $this->hasher                        = $hasher;
        $this->dao                           = $dao;
        $this->scope_saver                   = $scope_saver;
        $this->access_token_expiration_delay = $access_token_expiration_delay;
        $this->transaction_executor          = $transaction_executor;
    }

    /**
     * @param AuthenticationScope[] $scopes
     *
     * @psalm-param non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    public function issueAccessToken(\DateTimeImmutable $current_time, int $authorization_grant_id, array $scopes): OAuth2AccessTokenWithIdentifier
    {
        $verification_string = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $expiration_date     = $current_time->add($this->access_token_expiration_delay);

        $access_token_id = $this->transaction_executor->execute(
            function () use ($verification_string, $expiration_date, $scopes, $authorization_grant_id): int {
                $access_token_id = $this->dao->create(
                    $authorization_grant_id,
                    $this->hasher->computeHash($verification_string),
                    $expiration_date->getTimestamp()
                );
                $this->scope_saver->saveScopes($access_token_id, $scopes);

                return $access_token_id;
            }
        );

        return new OAuth2AccessTokenWithIdentifier(
            $this->access_token_formatter->getIdentifier(new SplitToken($access_token_id, $verification_string)),
            $expiration_date
        );
    }
}
