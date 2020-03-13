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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

final class AuthorizationCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var AuthorizationCreator */
    private $creator;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthorizationDao
     */
    private $authorization_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthorizationScopeDao
     */
    private $scope_dao;

    protected function setUp(): void
    {
        $this->authorization_dao = M::mock(AuthorizationDao::class);
        $this->scope_dao         = M::mock(AuthorizationScopeDao::class);
        $this->creator           = new AuthorizationCreator(
            new DBTransactionExecutorPassthrough(),
            $this->authorization_dao,
            $this->scope_dao
        );
    }

    public function testSaveAuthorization(): void
    {
        $user            = UserTestBuilder::aUser()->withId(102)->build();
        $app_id          = 65;
        $foobar_scope    = OAuth2ScopeIdentifier::fromIdentifierKey('foo:bar');
        $typevalue_scope = OAuth2ScopeIdentifier::fromIdentifierKey('type:value');

        $this->authorization_dao->shouldReceive('searchAuthorization')
            ->once()
            ->with($user, $app_id)
            ->andReturnNull();
        $this->authorization_dao->shouldReceive('create')
            ->once()
            ->with($user, $app_id)
            ->andReturn(17);
        $this->scope_dao->shouldReceive('deleteForAuthorization')
            ->once()
            ->with(17);
        $this->scope_dao->shouldReceive('createMany')
            ->once()
            ->with(17, $foobar_scope, $typevalue_scope);

        $this->creator->saveAuthorization(new NewAuthorization($user, $app_id, $foobar_scope, $typevalue_scope));
    }

    public function testSaveAuthorizationDoesNotSaveDuplicate(): void
    {
        $user            = UserTestBuilder::aUser()->withId(102)->build();
        $app_id          = 65;
        $foobar_scope    = OAuth2ScopeIdentifier::fromIdentifierKey('foo:bar');
        $typevalue_scope = OAuth2ScopeIdentifier::fromIdentifierKey('type:value');

        $this->authorization_dao->shouldReceive('searchAuthorization')
            ->once()
            ->with($user, $app_id)
            ->andReturn(17);
        $this->authorization_dao->shouldNotReceive('create');
        $this->scope_dao->shouldReceive('deleteForAuthorization')
            ->once()
            ->with(17);
        $this->scope_dao->shouldReceive('createMany')
            ->once()
            ->with(17, $foobar_scope, $typevalue_scope);

        $this->creator->saveAuthorization(new NewAuthorization($user, $app_id, $foobar_scope, $typevalue_scope));
    }
}
