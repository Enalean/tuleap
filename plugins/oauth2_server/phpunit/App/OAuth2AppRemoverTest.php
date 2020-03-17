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

namespace Tuleap\OAuth2Server\App;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;
use Tuleap\OAuth2Server\User\AuthorizationDao;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class OAuth2AppRemoverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRemovesAnApp(): void
    {
        $app_dao           = \Mockery::mock(AppDao::class);
        $auth_code_dao     = \Mockery::mock(OAuth2AuthorizationCodeDAO::class);
        $authorization_dao = \Mockery::mock(AuthorizationDao::class);

        $app_remover = new OAuth2AppRemover(
            $app_dao,
            $auth_code_dao,
            $authorization_dao,
            new DBTransactionExecutorPassthrough()
        );

        $app_dao->shouldReceive('delete')->with(12)->once();
        $auth_code_dao->shouldReceive('deleteAuthorizationCodeByAppID')->with(12)->once();
        $authorization_dao->shouldReceive('deleteAuthorizationByAppID')->with(12)->once();

        $app_remover->deleteAppByID(12);
    }
}
