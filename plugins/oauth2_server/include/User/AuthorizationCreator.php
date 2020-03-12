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

class AuthorizationCreator
{
    /**
     * @var DBTransactionExecutor
     */
    private $executor;
    /**
     * @var AuthorizationDao
     */
    private $authorization_dao;
    /**
     * @var AuthorizationScopeDao
     */
    private $scope_dao;

    public function __construct(
        DBTransactionExecutor $executor,
        AuthorizationDao $authorization_dao,
        AuthorizationScopeDao $scope_dao
    ) {
        $this->executor = $executor;
        $this->authorization_dao = $authorization_dao;
        $this->scope_dao = $scope_dao;
    }

    public function saveAuthorization(NewAuthorization $new_authorization): void
    {
        $this->executor->execute(
            function () use ($new_authorization) {
                $user             = $new_authorization->getUser();
                $app_id           = $new_authorization->getAppId();
                $authorization_id = $this->authorization_dao->searchAuthorization($user, $app_id);
                if ($authorization_id === null) {
                    $authorization_id = $this->authorization_dao->create($user, $app_id);
                }
                $this->scope_dao->deleteForAuthorization($authorization_id);
                $this->scope_dao->createMany($authorization_id, ...$new_authorization->getScopeIdentifiers());
            }
        );
    }
}
