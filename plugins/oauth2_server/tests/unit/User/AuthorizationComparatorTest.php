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

use Tuleap\Authentication\Scope\AuthenticationTestCoveringScope;
use Tuleap\Authentication\Scope\AuthenticationTestScopeIdentifier;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AuthorizationComparatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var AuthorizationComparator */
    private $comparator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthorizedScopeFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory    = $this->createMock(AuthorizedScopeFactory::class);
        $this->comparator = new AuthorizationComparator($this->factory);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderCoveringScopes')]
    public function testAreRequestedScopesAlreadyGranted(
        bool $expected_result,
        array $saved_scopes,
        array $requested_scopes,
    ): void {
        $user = UserTestBuilder::anAnonymousUser()->build();
        $app  = new OAuth2App(17, 'Jenkins', 'https://example.com', true, new \Project(['group_id' => 102]));
        $this->factory->expects(self::once())->method('getAuthorizedScopes')
            ->with($user, $app)
            ->willReturn($saved_scopes);
        self::assertSame(
            $expected_result,
            $this->comparator->areRequestedScopesAlreadyGranted($user, $app, $requested_scopes)
        );
    }

    public static function dataProviderCoveringScopes(): array
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
