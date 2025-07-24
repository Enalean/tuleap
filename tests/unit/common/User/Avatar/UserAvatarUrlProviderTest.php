<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\User\Avatar;

use ForgeConfig;
use org\bovigo\vfs\vfsStream;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\User\Avatar\AvatarHashStorageStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserAvatarUrlProviderTest extends TestCase
{
    use ForgeConfigSandbox;

    private const HTTPS_HOST_IN_TESTS = 'https://';

    #[\Override]
    protected function setUp(): void
    {
        ForgeConfig::set('sys_avatar_path', vfsStream::setup()->url());
    }

    public function testDefaultUrlForAnonymousUser(): void
    {
        $storage = AvatarHashStorageStub::withoutStoredHash();

        $provider = new UserAvatarUrlProvider($storage, new ComputeAvatarHash());

        self::assertSame(
            $this->getExpected(\PFUser::DEFAULT_AVATAR_URL),
            $provider->getAvatarUrl(UserTestBuilder::anAnonymousUser()->build()),
        );
    }

    public function testDefaultUrlForUserWithEmptyRealName(): void
    {
        $storage = AvatarHashStorageStub::withoutStoredHash();

        $provider = new UserAvatarUrlProvider($storage, new ComputeAvatarHash());

        $user = UserTestBuilder::aUser()
            ->withId(101)
            ->withRealName('')
            ->withUserName('systemuser')
            ->build();

        self::assertSame(
            $this->getExpected(\PFUser::DEFAULT_AVATAR_URL),
            $provider->getAvatarUrl($user),
        );
    }

    public function testDummyAvatarUrlForUserWithMissingAvatarFile(): void
    {
        $storage = AvatarHashStorageStub::withoutStoredHash();

        $provider = new UserAvatarUrlProvider($storage, new ComputeAvatarHash());

        $user = UserTestBuilder::aUser()
            ->withId(101)
            ->withRealName('John Doe')
            ->withUserName('jdoe')
            ->build();

        self::assertSame(
            $this->getExpected('/users/jdoe/avatar.png'),
            $provider->getAvatarUrl($user),
        );
    }

    public function testUserAvatarUrlForUserWithAvatarFileWithHashNotCached(): void
    {
        $storage = AvatarHashStorageStub::withoutStoredHash();

        $provider = new UserAvatarUrlProvider($storage, new ComputeAvatarHash());

        $user = UserTestBuilder::aUser()
            ->withId(101)
            ->withRealName('John Doe')
            ->withUserName('jdoe')
            ->build();

        $path = $user->getAvatarFilePath();
        mkdir(dirname($path), 0777, true);
        file_put_contents($path, 'png content');

        self::assertSame(
            $this->getExpected('/users/jdoe/avatar-47c5980ff911e17a2e60e068e79fbfc52c7f36e518a38a37e4dfc69650138bd7.png'),
            $provider->getAvatarUrl($user),
        );
        self::assertSame(
            '47c5980ff911e17a2e60e068e79fbfc52c7f36e518a38a37e4dfc69650138bd7',
            $storage->getNewStoredHash(),
        );
    }

    public function testItDoesNotNeedToComputeTheHashIfItHasAlreadyBeenComputed(): void
    {
        $storage = AvatarHashStorageStub::withStoredHash('47c5980ff911e17a2e60e068e79fbfc52c7f36e518a38a37e4dfc69650138bd7');

        $provider = new UserAvatarUrlProvider($storage, new ComputeAvatarHash());

        $user = UserTestBuilder::aUser()
            ->withId(101)
            ->withRealName('John Doe')
            ->withUserName('jdoe')
            ->build();

        self::assertSame(
            $this->getExpected('/users/jdoe/avatar-47c5980ff911e17a2e60e068e79fbfc52c7f36e518a38a37e4dfc69650138bd7.png'),
            $provider->getAvatarUrl($user),
        );
        self::assertNull(
            $storage->getNewStoredHash(),
        );
    }

    private function getExpected(string $epected): string
    {
        return self::HTTPS_HOST_IN_TESTS . $epected;
    }
}
