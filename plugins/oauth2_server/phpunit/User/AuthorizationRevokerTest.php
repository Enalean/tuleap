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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class AuthorizationRevokerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var AuthorizationRevoker
     */
    private $authorization_revoker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2AuthorizationCodeDAO
     */
    private $auth_code_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AuthorizationDao
     */
    private $authorization_dao;

    protected function setUp(): void
    {
        $this->auth_code_dao     = \Mockery::mock(OAuth2AuthorizationCodeDAO::class);
        $this->authorization_dao = \Mockery::mock(AuthorizationDao::class);

        $this->authorization_revoker = new AuthorizationRevoker(
            $this->auth_code_dao,
            $this->authorization_dao,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testDoesAuthorizationExistReturnsTrue(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $this->authorization_dao->shouldReceive('searchAuthorization')
            ->with($user, 13)
            ->once()
            ->andReturn(46);

        $this->assertTrue($this->authorization_revoker->doesAuthorizationExist($user, 13));
    }

    public function testDoesAuthorizationExistReturnsFalse(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $this->authorization_dao->shouldReceive('searchAuthorization')
            ->with($user, 13)
            ->once()
            ->andReturnNull();

        $this->assertFalse($this->authorization_revoker->doesAuthorizationExist($user, 13));
    }

    public function testRevokeAppAuthorization(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $this->authorization_dao->shouldReceive('deleteAuthorizationByUserAndAppID')->with($user, 12)->once();
        $this->auth_code_dao->shouldReceive('deleteAuthorizationCodeByUserAndAppID')->with($user, 12)->once();

        $this->authorization_revoker->revokeAppAuthorization($user, 12);
    }
}
