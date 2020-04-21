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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;

final class AccessKeyScopeRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AccessKeyScopeDAO
     */
    private $scope_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AuthenticationScopeBuilder
     */
    private $key_scope_builder;

    /**
     * @var AccessKeyScopeRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->scope_dao         = \Mockery::mock(AccessKeyScopeDAO::class);
        $this->key_scope_builder = \Mockery::mock(AuthenticationScopeBuilder::class);

        $this->retriever = new AccessKeyScopeRetriever($this->scope_dao, $this->key_scope_builder);
    }

    public function testRetrievesScopesAssociatedWithAKey(): void
    {
        $this->scope_dao->shouldReceive('searchScopeKeysByAccessKeyID')->andReturn([
            ['scope_key' => 'foo:bar'],
            ['scope_key' => 'type:value'],
        ]);

        $this->key_scope_builder->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')->with(
            \Mockery::on(static function (AuthenticationScopeIdentifier $scope_identifier): bool {
                return 'foo:bar' === $scope_identifier->toString();
            })
        )->once()->andReturn(\Mockery::mock(AuthenticationScope::class));
        $this->key_scope_builder->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')->with(
            \Mockery::on(static function (AuthenticationScopeIdentifier $scope_identifier): bool {
                return 'type:value' === $scope_identifier->toString();
            })
        )->once()->andReturn(\Mockery::mock(AuthenticationScope::class));

        $scopes = $this->retriever->getScopesByAccessKeyID(12);

        $this->assertCount(2, $scopes);
    }

    public function testOnlyRetrievesBuildableScopes(): void
    {
        $this->scope_dao->shouldReceive('searchScopeKeysByAccessKeyID')->andReturn([
            ['scope_key' => 'foo:baz']
        ]);

        $this->key_scope_builder->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')->andReturn(null);

        $scopes = $this->retriever->getScopesByAccessKeyID(13);

        $this->assertEmpty($scopes);
    }
}
