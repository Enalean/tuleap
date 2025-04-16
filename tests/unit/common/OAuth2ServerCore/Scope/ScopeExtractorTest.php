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

namespace Tuleap\OAuth2ServerCore\Scope;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ScopeExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var ScopeExtractor */
    private $scope_extractor;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthenticationScopeBuilder
     */
    private $scope_builder;

    protected function setUp(): void
    {
        $this->scope_builder   = $this->createMock(AuthenticationScopeBuilder::class);
        $this->scope_extractor = new ScopeExtractor($this->scope_builder);
    }

    public function testExtractScopesThrowsWhenScopeHasInvalidFormat(): void
    {
        $this->expectException(InvalidOAuth2ScopeException::class);
        $this->scope_extractor->extractScopes('@invalid_scope_format#');
    }

    public function testExtractScopesThrowsWhenScopeHasUnknownIdentifier(): void
    {
        $this->scope_builder->expects($this->once())->method('buildAuthenticationScopeFromScopeIdentifier')
            ->with(
                self::callback(
                    static function (AuthenticationScopeIdentifier $scope_identifier): bool {
                        return 'unknown:scope' === $scope_identifier->toString();
                    }
                )
            )
            ->willReturn(null);

        $this->expectException(InvalidOAuth2ScopeException::class);
        $this->scope_extractor->extractScopes('unknown:scope');
    }

    public function testExtractScopesFromQueryAndSplitsOnSpaceCharacter(): void
    {
        $foobar_scope    = $this->createMock(AuthenticationScope::class);
        $typevalue_scope = $this->createMock(AuthenticationScope::class);
        $this->scope_builder->expects($this->exactly(2))->method('buildAuthenticationScopeFromScopeIdentifier')->with(
            self::callback(
                static function (AuthenticationScopeIdentifier $scope_identifier): bool {
                    $raw_scope_identifier = $scope_identifier->toString();
                    return 'foo:bar' === $raw_scope_identifier || 'type:value' === $raw_scope_identifier;
                }
            )
        )->willReturn($foobar_scope);

        $scopes = $this->scope_extractor->extractScopes('foo:bar type:value');
        $this->assertEquals([$foobar_scope, $typevalue_scope], $scopes);
    }

    public function testExtractScopeFromQueryWithoutDuplicates(): void
    {
        $foobar_scope = $this->createMock(AuthenticationScope::class);
        $this->scope_builder->method('buildAuthenticationScopeFromScopeIdentifier')->with(
            self::callback(
                static function (AuthenticationScopeIdentifier $scope_identifier): bool {
                    return 'foo:bar' === $scope_identifier->toString();
                }
            )
        )->willReturn($foobar_scope);

        $scopes = $this->scope_extractor->extractScopes('foo:bar foo:bar');
        $this->assertEquals([$foobar_scope], $scopes);
    }
}
