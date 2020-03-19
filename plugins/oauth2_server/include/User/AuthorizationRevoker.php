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

namespace Tuleap\OAuth2Server\User;

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;

class AuthorizationRevoker
{
    /**
     * @var OAuth2AuthorizationCodeDAO
     */
    private $authorization_code_DAO;
    /**
     * @var AuthorizationDao
     */
    private $authorization_dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        OAuth2AuthorizationCodeDAO $authorization_code_DAO,
        AuthorizationDao $authorization_dao,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->authorization_code_DAO = $authorization_code_DAO;
        $this->authorization_dao      = $authorization_dao;
        $this->transaction_executor   = $transaction_executor;
    }

    public function doesAuthorizationExist(\PFUser $user, int $app_id): bool
    {
        $authorization = $this->authorization_dao->searchAuthorization($user, $app_id);
        return $authorization !== null;
    }

    public function revokeAppAuthorization(\PFUser $user, int $app_id): void
    {
        $this->transaction_executor->execute(
            function () use ($user, $app_id): void {
                $this->authorization_dao->deleteAuthorizationByUserAndAppID($user, $app_id);
                $this->authorization_code_DAO->deleteAuthorizationCodeByUserAndAppID($user, $app_id);
            }
        );
    }
}
