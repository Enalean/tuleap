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
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2ScopeRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2ScopeIdentifierSearcherDAO
     */
    private $scope_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthenticationScopeBuilder
     */
    private $scope_builder;
    /**
     * @var OAuth2ScopeRetriever
     */
    private $retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->scope_dao     = $this->createMock(OAuth2ScopeIdentifierSearcherDAO::class);
        $this->scope_builder = $this->createMock(AuthenticationScopeBuilder::class);

        $this->retriever = new OAuth2ScopeRetriever($this->scope_dao, $this->scope_builder);
    }

    public function testRetrievesScopesAssociatedWithAToken(): void
    {
        $this->scope_dao->method('searchScopeIdentifiersByOAuth2SplitTokenID')->willReturn([
            ['scope_key' => 'profile'],
            ['scope_key' => 'somethingspecific:read'],
        ]);

        $auth_scope = $this->createMock(AuthenticationScope::class);
        $this->scope_builder->expects($this->exactly(2))->method('buildAuthenticationScopeFromScopeIdentifier')
            ->willReturnCallback(
                static function (AuthenticationScopeIdentifier $scope_identifier) use ($auth_scope): AuthenticationScope {
                    $raw_scope_identifier = $scope_identifier->toString();
                    if ($raw_scope_identifier === 'profile' || $raw_scope_identifier === 'somethingspecific:read') {
                        return $auth_scope;
                    }
                    throw new \LogicException('Do not expect the scope identifier ' . $raw_scope_identifier);
                }
            );

        $scopes = $this->retriever->getScopesBySplitToken($this->buildToken());

        $this->assertCount(2, $scopes);
    }

    public function testOnlyRetrievesBuildableScopes(): void
    {
        $this->scope_dao->method('searchScopeIdentifiersByOAuth2SplitTokenID')->willReturn([
            ['scope_key' => 'unknown'],
        ]);

        $this->scope_builder->method('buildAuthenticationScopeFromScopeIdentifier')->willReturn(null);

        $scopes = $this->retriever->getScopesBySplitToken($this->buildToken());

        $this->assertEmpty($scopes);
    }

    private function buildToken(): SplitToken
    {
        return new SplitToken(10, SplitTokenVerificationString::generateNewSplitTokenVerificationString());
    }
}
