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

namespace Tuleap\OAuth2Server\AccessToken\Scope;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;

final class OAuth2AccessTokenScopeRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2AccessTokenScopeDAO
     */
    private $scope_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AuthenticationScopeBuilder
     */
    private $scope_builder;
    /**
     * @var OAuth2AccessTokenScopeRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->scope_dao     = \Mockery::mock(OAuth2AccessTokenScopeDAO::class);
        $this->scope_builder = \Mockery::mock(AuthenticationScopeBuilder::class);

        $this->retriever = new OAuth2AccessTokenScopeRetriever($this->scope_dao, $this->scope_builder);
    }

    public function testRetrievesScopesAssociatedWithAnAccessToken(): void
    {
        $this->scope_dao->shouldReceive('searchScopeIdentifiersByAccessTokenID')->andReturn([
            ['scope_key' => 'profile'],
            ['scope_key' => 'somethingspecific:read'],
        ]);

        $this->scope_builder->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')->with(
            \Mockery::on(static function (AuthenticationScopeIdentifier $scope_identifier): bool {
                return 'profile' === $scope_identifier->toString();
            })
        )->once()->andReturn(\Mockery::mock(AuthenticationScope::class));
        $this->scope_builder->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')->with(
            \Mockery::on(static function (AuthenticationScopeIdentifier $scope_identifier): bool {
                return 'somethingspecific:read' === $scope_identifier->toString();
            })
        )->once()->andReturn(\Mockery::mock(AuthenticationScope::class));

        $scopes = $this->retriever->getScopesByAccessToken($this->buildToken());

        $this->assertCount(2, $scopes);
    }

    public function testOnlyRetrievesBuildableScopes(): void
    {
        $this->scope_dao->shouldReceive('searchScopeIdentifiersByAccessTokenID')->andReturn([
            ['scope_key' => 'unknown']
        ]);

        $this->scope_builder->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')->andReturn(null);

        $scopes = $this->retriever->getScopesByAccessToken($this->buildToken());

        $this->assertEmpty($scopes);
    }

    private function buildToken(): SplitToken
    {
        return new SplitToken(98, \Mockery::mock(SplitTokenVerificationString::class));
    }
}
