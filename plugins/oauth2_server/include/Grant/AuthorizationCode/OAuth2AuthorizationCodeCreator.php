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

use DateInterval;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Scope\OAuth2ScopeSaver;

class OAuth2AuthorizationCodeCreator
{
    /**
     * @var SplitTokenFormatter
     */
    private $authorization_code_formatter;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var OAuth2AuthorizationCodeDAO
     */
    private $authorization_code_dao;
    /**
     * @var OAuth2ScopeSaver
     */
    private $authorization_code_scope_saver;
    /**
     * @var DateInterval
     */
    private $access_token_expiration_delay;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        SplitTokenFormatter $authorization_code_formatter,
        SplitTokenVerificationStringHasher $hasher,
        OAuth2AuthorizationCodeDAO $authorization_code_dao,
        OAuth2ScopeSaver $authorization_code_scope_saver,
        DateInterval $access_token_expiration_delay,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->authorization_code_formatter      = $authorization_code_formatter;
        $this->hasher                            = $hasher;
        $this->authorization_code_dao            = $authorization_code_dao;
        $this->authorization_code_scope_saver    = $authorization_code_scope_saver;
        $this->access_token_expiration_delay     = $access_token_expiration_delay;
        $this->transaction_executor              = $transaction_executor;
    }

    /**
     * @param AuthenticationScope[] $scopes
     *
     * @psalm-param non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    public function createAuthorizationCodeIdentifier(
        \DateTimeImmutable $current_time,
        OAuth2App $app,
        array $scopes,
        \PFUser $user,
        ?string $pkce_code_challenge
    ): ConcealedString {
        $verification_string = SplitTokenVerificationString::generateNewSplitTokenVerificationString();
        $expiration_date     = $current_time->add($this->access_token_expiration_delay);

        $authorization_code_id = $this->transaction_executor->execute(
            function () use ($app, $user, $verification_string, $expiration_date, $scopes, $pkce_code_challenge) : int {
                $authorization_code_id = $this->authorization_code_dao->create(
                    $app->getId(),
                    (int) $user->getId(),
                    $this->hasher->computeHash($verification_string),
                    $expiration_date->getTimestamp(),
                    $pkce_code_challenge
                );
                $this->authorization_code_scope_saver->saveScopes($authorization_code_id, $scopes);

                return $authorization_code_id;
            }
        );

        return $this->authorization_code_formatter->getIdentifier(new SplitToken($authorization_code_id, $verification_string));
    }
}
