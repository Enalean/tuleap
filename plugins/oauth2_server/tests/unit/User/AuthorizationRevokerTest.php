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

use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AuthorizationRevokerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var AuthorizationRevoker
     */
    private $authorization_revoker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AuthorizationCodeDAO
     */
    private $auth_code_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthorizationDao
     */
    private $authorization_dao;

    protected function setUp(): void
    {
        $this->auth_code_dao     = $this->createMock(OAuth2AuthorizationCodeDAO::class);
        $this->authorization_dao = $this->createMock(AuthorizationDao::class);

        $this->authorization_revoker = new AuthorizationRevoker(
            $this->auth_code_dao,
            $this->authorization_dao,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testDoesAuthorizationExistReturnsTrue(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $this->authorization_dao->expects($this->once())->method('searchAuthorization')
            ->with($user, 13)
            ->willReturn(46);

        $this->assertTrue($this->authorization_revoker->doesAuthorizationExist($user, 13));
    }

    public function testDoesAuthorizationExistReturnsFalse(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $this->authorization_dao->expects($this->once())->method('searchAuthorization')
            ->with($user, 13)
            ->willReturn(null);

        $this->assertFalse($this->authorization_revoker->doesAuthorizationExist($user, 13));
    }

    public function testRevokeAppAuthorization(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $this->authorization_dao->expects($this->once())->method('deleteAuthorizationByUserAndAppID')->with($user, 12);
        $this->auth_code_dao->expects($this->once())->method('deleteAuthorizationCodeByUserAndAppID')->with($user, 12);

        $this->authorization_revoker->revokeAppAuthorization($user, 12);
    }
}
