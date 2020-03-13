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
use Tuleap\Authentication\Scope\AuthenticationTestScopeIdentifier;
use Tuleap\Test\Builders\UserTestBuilder;

final class AuthorizationComparatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var AuthorizationComparator */
    private $comparator;
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
        $scope_builder = new class implements AuthenticationScopeBuilder {
            public function buildAuthenticationScopeFromScopeIdentifier(
                AuthenticationScopeIdentifier $scope_identifier
            ): AuthenticationScope {
                return AuthenticationTestCoveringScope::fromIdentifier($scope_identifier);
            }

            public function buildAllAvailableAuthenticationScopes(): array
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }
        };

        $this->authorization_dao = M::mock(AuthorizationDao::class);
        $this->scope_dao         = M::mock(AuthorizationScopeDao::class);
        $this->comparator        = new AuthorizationComparator(
            $this->authorization_dao,
            $this->scope_dao,
            $scope_builder
        );
    }

    public function testAreRequestedScopesAlreadyGrantedReturnsFalseWhenNoPreviousAuthorization(): void
    {
        $foobar_scope    = M::mock(AuthenticationScope::class);
        $typevalue_scope = M::mock(AuthenticationScope::class);
        $user            = UserTestBuilder::anAnonymousUser()->build();
        $app_id          = 12;
        $this->authorization_dao->shouldReceive('searchAuthorization')
            ->once()
            ->with($user, $app_id)
            ->andReturnNull();

        $this->assertFalse(
            $this->comparator->areRequestedScopesAlreadyGranted($user, $app_id, $foobar_scope, $typevalue_scope)
        );
    }

    /**
     * @dataProvider dataProviderCoveringScopes
     */
    public function testAreRequestedScopesAlreadyGranted(
        bool $expected_result,
        array $saved_scopes,
        array $requested_scopes
    ): void {
        $this->authorization_dao->shouldReceive('searchAuthorization')->once()->andReturn(12);
        $this->scope_dao->shouldReceive('searchScopes')->once()->with(12)->andReturn($saved_scopes);
        $this->assertSame(
            $expected_result,
            $this->comparator->areRequestedScopesAlreadyGranted(
                UserTestBuilder::anAnonymousUser()->build(),
                12,
                ...$requested_scopes
            )
        );
    }

    public function dataProviderCoveringScopes(): array
    {
        $foobar_scope     = AuthenticationTestCoveringScope::fromIdentifier(
            AuthenticationTestScopeIdentifier::fromIdentifierKey('foo:bar')
        );
        $typevalue_scope  = AuthenticationTestCoveringScope::fromIdentifier(
            AuthenticationTestScopeIdentifier::fromIdentifierKey('type:value')
        );
        $flobwobble_scope = AuthenticationTestCoveringScope::fromIdentifier(
            AuthenticationTestScopeIdentifier::fromIdentifierKey('flob:wobble')
        );
        return [
            'Neither saved nor requested scope'           => [true, [], []],
            'Saved scope does not match requested scope'  => [false, ['foo:bar'], [$typevalue_scope]],
            'Fewer saved scopes than requested scopes'    => [false, ['foo:bar'], [$foobar_scope, $typevalue_scope]],
            'Saved scopes do not include requested scope' => [false, ['foo:bar', 'type:value'], [$flobwobble_scope]],
            'No saved scope'                              => [false, [], [$foobar_scope]],
            'No requested scope'                          => [true, ['foo:bar'], []],
            'Saved scope matches requested scope'         => [true, ['foo:bar'], [$foobar_scope]],
            'Saved scopes match requested scopes'         => [true, ['foo:bar', 'type:value'], [$foobar_scope, $typevalue_scope]],
            'More saved scopes than requested scopes'     => [true, ['foo:bar', 'type:value'], [$typevalue_scope]],
        ];
    }
}
