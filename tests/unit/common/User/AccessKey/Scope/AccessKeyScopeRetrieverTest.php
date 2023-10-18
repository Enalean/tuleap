<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\User\AccessKey\Scope;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;

final class AccessKeyScopeRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessKeyScopeDAO
     */
    private $scope_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthenticationScopeBuilder
     */
    private $key_scope_builder;

    /**
     * @var AccessKeyScopeRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->scope_dao         = $this->createMock(AccessKeyScopeDAO::class);
        $this->key_scope_builder = $this->createMock(AuthenticationScopeBuilder::class);

        $this->retriever = new AccessKeyScopeRetriever($this->scope_dao, $this->key_scope_builder);
    }

    public function testRetrievesScopesAssociatedWithAKey(): void
    {
        $this->scope_dao->method('searchScopeKeysByAccessKeyID')->willReturn([
            ['scope_key' => 'foo:bar'],
            ['scope_key' => 'type:value'],
        ]);

        $this->key_scope_builder->method('buildAuthenticationScopeFromScopeIdentifier')->willReturnCallback(
            function (AuthenticationScopeIdentifier $scope_identifier): AuthenticationScope&MockObject {
                if (
                    $scope_identifier->toString() === 'foo:bar' ||
                    $scope_identifier->toString() === 'type:value'
                ) {
                    return $this->createMock(AuthenticationScope::class);
                }

                throw new \LogicException('must not be here.');
            }
        );

        $scopes = $this->retriever->getScopesByAccessKeyID(12);

        self::assertCount(2, $scopes);
    }

    public function testOnlyRetrievesBuildableScopes(): void
    {
        $this->scope_dao->method('searchScopeKeysByAccessKeyID')->willReturn([
            ['scope_key' => 'foo:baz'],
        ]);

        $this->key_scope_builder->method('buildAuthenticationScopeFromScopeIdentifier')->willReturn(null);

        $scopes = $this->retriever->getScopesByAccessKeyID(13);

        self::assertEmpty($scopes);
    }
}
