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

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AuthorizationCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var AuthorizationCreator */
    private $creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthorizationDao
     */
    private $authorization_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthorizationScopeDao
     */
    private $scope_dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorization_dao = $this->createMock(AuthorizationDao::class);
        $this->scope_dao         = $this->createMock(AuthorizationScopeDao::class);
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

        $this->authorization_dao->expects($this->once())->method('searchAuthorization')
            ->with($user, $app_id)
            ->willReturn(null);
        $this->authorization_dao->expects($this->once())->method('create')
            ->with($user, $app_id)
            ->willReturn(17);
        $this->scope_dao->expects($this->once())->method('deleteForAuthorization')
            ->with(17);
        $this->scope_dao->expects($this->once())->method('createMany')
            ->with(17, $foobar_scope, $typevalue_scope);

        $this->creator->saveAuthorization(new NewAuthorization($user, $app_id, $foobar_scope, $typevalue_scope));
    }

    public function testSaveAuthorizationDoesNotSaveDuplicate(): void
    {
        $user            = UserTestBuilder::aUser()->withId(102)->build();
        $app_id          = 65;
        $foobar_scope    = OAuth2ScopeIdentifier::fromIdentifierKey('foo:bar');
        $typevalue_scope = OAuth2ScopeIdentifier::fromIdentifierKey('type:value');

        $this->authorization_dao->expects($this->once())->method('searchAuthorization')
            ->with($user, $app_id)
            ->willReturn(17);
        $this->authorization_dao->expects($this->never())->method('create');
        $this->scope_dao->expects($this->once())->method('deleteForAuthorization')
            ->with(17);
        $this->scope_dao->expects($this->once())->method('createMany')
            ->with(17, $foobar_scope, $typevalue_scope);

        $this->creator->saveAuthorization(new NewAuthorization($user, $app_id, $foobar_scope, $typevalue_scope));
    }
}
