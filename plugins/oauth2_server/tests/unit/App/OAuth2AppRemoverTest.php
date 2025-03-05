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

use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;
use Tuleap\OAuth2Server\User\AuthorizationDao;
use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2AppRemoverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testRemovesAnApp(): void
    {
        $app_dao           = $this->createMock(AppDao::class);
        $auth_code_dao     = $this->createMock(OAuth2AuthorizationCodeDAO::class);
        $authorization_dao = $this->createMock(AuthorizationDao::class);

        $app_remover = new OAuth2AppRemover(
            $app_dao,
            $auth_code_dao,
            $authorization_dao,
            new DBTransactionExecutorPassthrough()
        );

        $app_dao->expects(self::once())->method('delete')->with(12);
        $auth_code_dao->expects(self::once())->method('deleteAuthorizationCodeByAppID')->with(12);
        $authorization_dao->expects(self::once())->method('deleteAuthorizationByAppID')->with(12);

        $app_remover->deleteAppByID(12);
    }
}
