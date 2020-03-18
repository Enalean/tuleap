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
use Tuleap\Authentication\Scope\AuthenticationTestCoveringScope;
use Tuleap\Authentication\Scope\AuthenticationTestScopeIdentifier;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\Test\Builders\UserTestBuilder;

final class AuthorizationComparatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var AuthorizationComparator */
    private $comparator;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthorizedScopeFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory    = M::mock(AuthorizedScopeFactory::class);
        $this->comparator = new AuthorizationComparator($this->factory);
    }

    /**
     * @dataProvider dataProviderCoveringScopes
     */
    public function testAreRequestedScopesAlreadyGranted(
        bool $expected_result,
        array $saved_scopes,
        array $requested_scopes
    ): void {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $app  = new OAuth2App(17, 'Jenkins', 'https://example.com', true, new \Project(['group_id' => 102]));
        $this->factory->shouldReceive('getAuthorizedScopes')
            ->with($user, $app)
            ->once()
            ->andReturn($saved_scopes);
        $this->assertSame(
            $expected_result,
            $this->comparator->areRequestedScopesAlreadyGranted($user, $app, $requested_scopes)
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
            'Saved scope does not match requested scope'  => [false, [$foobar_scope], [$typevalue_scope]],
            'Fewer saved scopes than requested scopes'    => [false, [$foobar_scope], [$foobar_scope, $typevalue_scope]],
            'Saved scopes do not include requested scope' => [false, [$foobar_scope, $typevalue_scope], [$flobwobble_scope]],
            'No saved scope'                              => [false, [], [$foobar_scope]],
            'No requested scope'                          => [true, [$foobar_scope], []],
            'Saved scope matches requested scope'         => [true, [$foobar_scope], [$foobar_scope]],
            'Saved scopes match requested scopes'         => [true, [$foobar_scope, $typevalue_scope], [$foobar_scope, $typevalue_scope]],
            'More saved scopes than requested scopes'     => [true, [$foobar_scope, $typevalue_scope], [$typevalue_scope]],
        ];
    }
}
