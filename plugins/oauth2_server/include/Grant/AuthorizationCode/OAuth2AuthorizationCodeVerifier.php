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

use DateTimeImmutable;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DBTransactionExecutor;
use UserManager;

class OAuth2AuthorizationCodeVerifier
{
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var OAuth2AuthorizationCodeDAO
     */
    private $authorization_code_dao;
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;

    public function __construct(
        SplitTokenVerificationStringHasher $hasher,
        UserManager $user_manager,
        OAuth2AuthorizationCodeDAO $authorization_code_dao,
        DBTransactionExecutor $db_transaction_executor
    ) {
        $this->hasher                  = $hasher;
        $this->user_manager            = $user_manager;
        $this->authorization_code_dao  = $authorization_code_dao;
        $this->db_transaction_executor = $db_transaction_executor;
    }

    /**
     * @throws OAuth2AuthCodeNotFoundException
     * @throws InvalidOAuth2AuthCodeException
     */
    public function getAuthorizationCode(SplitToken $auth_code): OAuth2AuthorizationCode
    {
        return $this->db_transaction_executor->execute(
            function () use ($auth_code): OAuth2AuthorizationCode {
                $row = $this->authorization_code_dao->searchAuthorizationCode($auth_code->getID());
                if ($row === null) {
                    throw new OAuth2AuthCodeNotFoundException($auth_code->getID());
                }

                $is_valid_auth_code = $this->hasher->verifyHash($auth_code->getVerificationString(), $row['verifier']);
                if (! $is_valid_auth_code) {
                    throw new InvalidOAuth2AuthCodeException();
                }

                if ($this->isAuthorizationCodeExpired($row['expiration_date'])) {
                    throw new OAuth2AuthCodeExpiredException($auth_code);
                }

                $this->authorization_code_dao->markAuthorizationCodeAsUsed($auth_code->getID());

                $user = $this->user_manager->getUserById($row['user_id']);
                if ($user === null) {
                    throw new OAuth2AuthCodeMatchingUnknownUserException($row['user_id']);
                }
                return OAuth2AuthorizationCode::approveForDemoScope($user);
            }
        );
    }

    private function isAuthorizationCodeExpired(int $expiration_timestamp): bool
    {
        $current_time = new DateTimeImmutable();

        return $expiration_timestamp <= $current_time->getTimestamp();
    }
}
