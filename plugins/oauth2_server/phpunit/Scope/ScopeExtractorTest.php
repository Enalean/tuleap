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

namespace Tuleap\OAuth2Server\Scope;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;

final class ScopeExtractorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ScopeExtractor */
    private $scope_extractor;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthenticationScopeBuilder
     */
    private $scope_builder;

    protected function setUp(): void
    {
        $this->scope_builder   = M::mock(AuthenticationScopeBuilder::class);
        $this->scope_extractor = new ScopeExtractor($this->scope_builder);
    }

    public function testExtractScopesThrowsWhenScopeHasInvalidFormat(): void
    {
        $this->expectException(InvalidOAuth2ScopeException::class);
        $this->scope_extractor->extractScopes('@invalid_scope_format#');
    }

    public function testExtractScopesThrowsWhenScopeHasUnknownIdentifier(): void
    {
        $this->scope_builder->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')
            ->with(
                M::on(
                    static function (AuthenticationScopeIdentifier $scope_identifier): bool {
                        return 'unknown:scope' === $scope_identifier->toString();
                    }
                )
            )
            ->once()
            ->andReturnNull();

        $this->expectException(InvalidOAuth2ScopeException::class);
        $this->scope_extractor->extractScopes('unknown:scope');
    }

    public function testExtractScopesFromQueryAndSplitsOnSpaceCharacter(): void
    {
        $foobar_scope    = M::mock(AuthenticationScope::class);
        $typevalue_scope = M::mock(AuthenticationScope::class);
        $this->scope_builder->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')->with(
            M::on(
                static function (AuthenticationScopeIdentifier $scope_identifier): bool {
                    return 'foo:bar' === $scope_identifier->toString();
                }
            )
        )->once()->andReturn($foobar_scope);
        $this->scope_builder->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')->with(
            M::on(
                static function (AuthenticationScopeIdentifier $scope_identifier): bool {
                    return 'type:value' === $scope_identifier->toString();
                }
            )
        )->once()->andReturn($foobar_scope);

        $scopes = $this->scope_extractor->extractScopes('foo:bar type:value');
        $this->assertEquals([$foobar_scope, $typevalue_scope], $scopes);
    }

    public function testExtractScopeFromQueryWithoutDuplicates(): void
    {
        $foobar_scope    = M::mock(AuthenticationScope::class);
        $this->scope_builder->shouldReceive('buildAuthenticationScopeFromScopeIdentifier')->with(
            M::on(
                static function (AuthenticationScopeIdentifier $scope_identifier): bool {
                    return 'foo:bar' === $scope_identifier->toString();
                }
            )
        )->once()->andReturn($foobar_scope);

        $scopes = $this->scope_extractor->extractScopes('foo:bar foo:bar');
        $this->assertEquals([$foobar_scope], $scopes);
    }
}
