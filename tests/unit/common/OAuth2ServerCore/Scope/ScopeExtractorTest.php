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

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ScopeExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ScopeExtractor $scope_extractor;
    private AuthenticationScopeBuilder&Stub $scope_builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->scope_builder   = $this->createStub(AuthenticationScopeBuilder::class);
        $this->scope_extractor = new ScopeExtractor($this->scope_builder);
    }

    public function testExtractScopesThrowsWhenScopeHasInvalidFormat(): void
    {
        $this->expectException(InvalidOAuth2ScopeException::class);
        $this->scope_extractor->extractScopes('@invalid_scope_format#');
    }

    public function testExtractScopesThrowsWhenScopeHasUnknownIdentifier(): void
    {
        $this->scope_builder->method('buildAuthenticationScopeFromScopeIdentifier')
            ->willReturn(null);

        $this->expectException(InvalidOAuth2ScopeException::class);
        $this->scope_extractor->extractScopes('unknown:scope');
    }

    public function testExtractScopesFromQueryAndSplitsOnSpaceCharacter(): void
    {
        $foobar_scope    = $this->createStub(AuthenticationScope::class);
        $typevalue_scope = $this->createStub(AuthenticationScope::class);
        $this->scope_builder->method('buildAuthenticationScopeFromScopeIdentifier')
            ->willReturnCallback(
                static fn (AuthenticationScopeIdentifier $scope_identifier) => match ($scope_identifier->toString()) {
                    'foo:bar' => $foobar_scope,
                    'type:value' => $typevalue_scope,
                }
            );

        $scopes = $this->scope_extractor->extractScopes('foo:bar type:value');

        /** @var AuthenticationScope[] $expected */
        $expected = [$foobar_scope, $typevalue_scope];

        self::assertSame($expected, $scopes);
    }

    public function testExtractScopeFromQueryWithoutDuplicates(): void
    {
        $foobar_scope = $this->createStub(AuthenticationScope::class);
        $this->scope_builder->method('buildAuthenticationScopeFromScopeIdentifier')
            ->willReturn($foobar_scope);

        $scopes = $this->scope_extractor->extractScopes('foo:bar foo:bar');

        /** @var AuthenticationScope[] $expected */
        $expected = [$foobar_scope];

        self::assertSame($expected, $scopes);
    }
}
