<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\User\Profile;

use ForgeAccess;
use ForgeConfig;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;
use Tuleap\Test\Stubs\CurrentRequestUserProviderStub;
use Tuleap\Test\Stubs\User\Avatar\AvatarHashStorageStub;
use Tuleap\User\Avatar\ComputeAvatarHash;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AvatarControllerTest extends TestCase
{
    use ForgeConfigSandbox;

    private \UserManager&\PHPUnit\Framework\MockObject\Stub $user_manager;
    private AvatarController $avatar_controller;

    #[\Override]
    protected function setUp(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        ForgeConfig::set('sys_avatar_path', vfsStream::setup()->url());

        $storage                 = AvatarHashStorageStub::withStoredHash('expected_hash');
        $compute_avatar_hash     = new ComputeAvatarHash();
        $response_factory        = HTTPFactoryBuilder::responseFactory();
        $this->user_manager      = $this->createStub(\UserManager::class);
        $this->avatar_controller = new AvatarController(
            new NoopSapiEmitter(),
            new BinaryFileResponseBuilder($response_factory, HTTPFactoryBuilder::streamFactory()),
            $response_factory,
            new CurrentRequestUserProviderStub(UserTestBuilder::anAnonymousUser()->build()),
            $this->user_manager,
            new AvatarGenerator($storage, $compute_avatar_hash),
            $storage,
            $compute_avatar_hash
        );
    }

    public function testRetrievesUserAvatarUsingANonPermanentURL(): void
    {
        $user = UserTestBuilder::anActiveUser()->withUserName('user1')->withRealName('User 1')->build();

        $request = (new NullServerRequest())->withAttribute('name', $user->getUserName());

        $this->user_manager->method('getUserByUserName')->with($user->getUserName())
            ->willReturn($user);
        $this->user_manager->method('updateDb');

        $response = $this->avatar_controller->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/png', $response->getHeaderLine('Content-Type'));
        self::assertSame('max-age=60', $response->getHeaderLine('Cache-Control'));
        self::assertStringContainsString("PNG\r\n", $response->getBody()->read(8));
    }

    public function testRetrievesUserAvatarUsingAPermanentURL(): void
    {
        $user = UserTestBuilder::anActiveUser()->withUserName('user1')->withRealName('User 1')->build();

        $request = (new NullServerRequest())
            ->withAttribute('name', $user->getUserName())
            ->withAttribute('hash', 'expected_hash');

        $this->user_manager->method('getUserByUserName')->with($user->getUserName())->willReturn($user);
        $this->user_manager->method('updateDb');

        $response = $this->avatar_controller->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/png', $response->getHeaderLine('Content-Type'));
        self::assertSame('max-age=31536000,immutable', $response->getHeaderLine('Cache-Control'));
        self::assertStringContainsString("PNG\r\n", $response->getBody()->read(8));
    }

    public function testRedirectsWhenTheUserAvatarHashIsSomethingElseThanTheProvidedValue(): void
    {
        $user = UserTestBuilder::anActiveUser()->withUserName('user1')->withRealName('User 1')->build();

        $request = (new NullServerRequest())
            ->withAttribute('name', $user->getUserName())
            ->withAttribute('hash', 'wrong_hash');

        $this->user_manager->method('getUserByUserName')->with($user->getUserName())->willReturn($user);
        $this->user_manager->method('updateDb');

        $response = $this->avatar_controller->handle($request);

        self::assertSame(301, $response->getStatusCode());
        self::assertStringEndsWith('/users/user1/avatar-expected_hash.png', $response->getHeaderLine('Location'));
        self::assertEmpty($response->getHeaderLine('Cache-Control'));
    }

    public function testReturnsA404WithADefaultAvatarWhenTheUserCannotBeFound(): void
    {
        $request = (new NullServerRequest())->withAttribute('name', 'does_not_exist');

        $this->user_manager->method('getUserByUserName')->willReturn(null);

        $response = $this->avatar_controller->handle($request);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('image/png', $response->getHeaderLine('Content-Type'));
        self::assertSame('max-age=60', $response->getHeaderLine('Cache-Control'));
        self::assertStringContainsString("PNG\r\n", $response->getBody()->read(8));
    }

    public function testReturnsA404WhenTheUserCannotHaveADefaultAvatar(): void
    {
        $user = UserTestBuilder::anActiveUser()->withUserName('not_avatar_possible')->withRealName('')->build();

        $request = (new NullServerRequest())->withAttribute('name', $user->getUserName());

        $this->user_manager->method('getUserByUserName')->willReturn($user);

        $response = $this->avatar_controller->handle($request);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('image/png', $response->getHeaderLine('Content-Type'));
        self::assertSame('max-age=60', $response->getHeaderLine('Cache-Control'));
        self::assertStringContainsString("PNG\r\n", $response->getBody()->read(8));
    }

    public function testReturnsA404ForAnonymousUserWhenTheInstanceDoNotAllowThem(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $request = (new NullServerRequest())->withAttribute('name', 'username');

        $response = $this->avatar_controller->handle($request);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('image/png', $response->getHeaderLine('Content-Type'));
        self::assertSame('max-age=60', $response->getHeaderLine('Cache-Control'));
        self::assertStringContainsString("PNG\r\n", $response->getBody()->read(8));
    }
}
