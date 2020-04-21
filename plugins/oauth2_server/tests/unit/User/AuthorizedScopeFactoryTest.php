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
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;
use Tuleap\Authentication\Scope\AuthenticationTestCoveringScope;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\Test\Builders\UserTestBuilder;

final class AuthorizedScopeFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var AuthorizedScopeFactory */
    private $factory;
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
        $scope_builder           = new class implements AuthenticationScopeBuilder {
            public function buildAuthenticationScopeFromScopeIdentifier(
                AuthenticationScopeIdentifier $scope_identifier
            ): ?AuthenticationScope {
                if ($scope_identifier->toString() === 'foo:bar' || $scope_identifier->toString() === 'type:value') {
                    return AuthenticationTestCoveringScope::fromIdentifier($scope_identifier);
                }
                return null;
            }

            public function buildAllAvailableAuthenticationScopes(): array
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }
        };
        $this->authorization_dao = M::mock(AuthorizationDao::class);
        $this->scope_dao         = M::mock(AuthorizationScopeDao::class);
        $this->factory           = new AuthorizedScopeFactory(
            $this->authorization_dao,
            $this->scope_dao,
            $scope_builder
        );
    }

    public function testGetAuthorizedScopesReturnsEmptyWhenNoAuthorizationCanBeFound(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $app  = new OAuth2App(17, 'Jenkins', 'https://example.com', true, new \Project(['group_id' => 102]));
        $this->authorization_dao->shouldReceive('searchAuthorization')
            ->once()
            ->with($user, 17)
            ->andReturnNull();

        $this->assertEmpty(
            $this->factory->getAuthorizedScopes($user, $app)
        );
    }

    public function testGetAuthorizedScopesReturnsScopes(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $app  = new OAuth2App(17, 'Jenkins', 'https://example.com', true, new \Project(['group_id' => 102]));
        $this->authorization_dao->shouldReceive('searchAuthorization')
            ->once()
            ->andReturn(12);
        $this->scope_dao->shouldReceive('searchScopes')
            ->once()
            ->with(12)
            ->andReturn(['foo:bar', 'type:value']);

        $saved_scopes = $this->factory->getAuthorizedScopes($user, $app);
        $this->assertSame(2, count($saved_scopes));
        $this->assertSame('foo:bar', $saved_scopes[0]->getIdentifier()->toString());
        $this->assertSame('type:value', $saved_scopes[1]->getIdentifier()->toString());
    }

    public function testGetAuthorizedScopesSkipsInvalidSavedScopes(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $app  = new OAuth2App(17, 'Jenkins', 'https://example.com', true, new \Project(['group_id' => 102]));
        $this->authorization_dao->shouldReceive('searchAuthorization')
            ->once()
            ->andReturn(12);
        $this->scope_dao->shouldReceive('searchScopes')
            ->once()
            ->with(12)
            ->andReturn(['flob:wobble', 'type:value']);

        $saved_scopes = $this->factory->getAuthorizedScopes($user, $app);
        $this->assertSame(1, count($saved_scopes));
        $this->assertSame('type:value', $saved_scopes[0]->getIdentifier()->toString());
    }
}
