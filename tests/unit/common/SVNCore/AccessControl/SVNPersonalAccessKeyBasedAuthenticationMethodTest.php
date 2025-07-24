<?php
/**
 * Copyright (c) Enalean 2023-Present. All rights reserved
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

namespace Tuleap\SVNCore\AccessControl;

use Psr\Log\NullLogger;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\AccessKey\AccessKeyException;
use Tuleap\User\AccessKey\AccessKeyVerifier;
use Tuleap\User\AccessKey\PrefixAccessKey;
use Tuleap\User\AccessKey\Scope\SVNAccessKeyScope;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SVNPersonalAccessKeyBasedAuthenticationMethodTest extends TestCase
{
    private const ACCESS_KEY = 'tlp-k1-123.aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    /**
     * @var AccessKeyVerifier&\PHPUnit\Framework\MockObject\Stub
     */
    private $access_key_verifier;
    /**
     * @var SVNLoginNameUserProvider&\PHPUnit\Framework\MockObject\Stub
     */
    private $user_provider;
    private SVNPersonalAccessKeyBasedAuthenticationMethod $auth_method;

    #[\Override]
    protected function setUp(): void
    {
        $this->access_key_verifier = $this->createStub(AccessKeyVerifier::class);
        $this->user_provider       = $this->createStub(SVNLoginNameUserProvider::class);
        $this->auth_method         = new SVNPersonalAccessKeyBasedAuthenticationMethod(
            new PrefixedSplitTokenSerializer(new PrefixAccessKey()),
            $this->access_key_verifier,
            SVNAccessKeyScope::fromItself(),
            $this->user_provider,
            new NullLogger(),
        );
    }

    public function testAuthenticationCanBeSuccessful(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();
        $this->access_key_verifier->method('getUser')->willReturn($user);
        $this->user_provider->method('getUserFromSVNLoginName')->willReturn($user);
        $authenticated_user = $this->auth_method->isAuthenticated('username', new ConcealedString(self::ACCESS_KEY), ProjectTestBuilder::aProject()->build(), new NullServerRequest());

        self::assertSame($user, $authenticated_user);
    }

    public function testAuthenticationIsRejectedWhenSecretIsNotAnAccessKey(): void
    {
        $user = $this->auth_method->isAuthenticated('username', new ConcealedString('not_an_access_key'), ProjectTestBuilder::aProject()->build(), new NullServerRequest());

        self::assertNull($user);
    }

    public function testAuthenticationIsRejectedWhenAccessKeyIsNotValid(): void
    {
        $this->access_key_verifier->method('getUser')->willThrowException(
            new class extends AccessKeyException {
            }
        );
        $user = $this->auth_method->isAuthenticated('username', new ConcealedString(self::ACCESS_KEY), ProjectTestBuilder::aProject()->build(), new NullServerRequest());

        self::assertNull($user);
    }

    public function testAuthenticationIsRejectedWhenNoUserMatchesTheSVNLoginName(): void
    {
        $this->access_key_verifier->method('getUser')->willReturn(UserTestBuilder::anActiveUser()->build());
        $this->user_provider->method('getUserFromSVNLoginName')->willReturn(null);
        $user = $this->auth_method->isAuthenticated('username', new ConcealedString(self::ACCESS_KEY), ProjectTestBuilder::aProject()->build(), new NullServerRequest());

        self::assertNull($user);
    }

    public function testAuthenticationIsRejectedWhenTheSVNLoginNameDoesNotMatchTheSameUserThanTheAccessKey(): void
    {
        $this->access_key_verifier->method('getUser')->willReturn(UserTestBuilder::anActiveUser()->withId(123)->build());
        $this->user_provider->method('getUserFromSVNLoginName')->willReturn(UserTestBuilder::anActiveUser()->withId(789)->build());
        $user = $this->auth_method->isAuthenticated('username', new ConcealedString(self::ACCESS_KEY), ProjectTestBuilder::aProject()->build(), new NullServerRequest());

        self::assertNull($user);
    }
}
