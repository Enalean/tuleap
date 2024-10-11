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

namespace Tuleap\User\Profile;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\User\Avatar\AvatarHashStorageDeletorStub;

final class ForceRegenerationDefaultAvatarCommandTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testCommandRemovesDefaultAvatar(): void
    {
        \ForgeConfig::set('sys_avatar_path', vfsStream::setup()->url());

        $user_manager = $this->createMock(\UserManager::class);
        $user_dao     = $this->createMock(\UserDao::class);
        $storage      = AvatarHashStorageDeletorStub::build();

        $user_dao->method('searchUsersWithDefaultAvatar')->willReturn([['user_id' => 102], ['user_id' => 103]]);
        $user_102 = UserTestBuilder::aUser()->withId(102)->build();
        $user_manager->method('getUserInstanceFromRow')->willReturn(
            $user_102,
            UserTestBuilder::aUser()->withId(103)->build(),
        );

        $user_102_avatar_file_path = $user_102->getAvatarFilePath();
        mkdir(dirname($user_102_avatar_file_path), 0777, true);
        touch($user_102_avatar_file_path);

        $command = new ForceRegenerationDefaultAvatarCommand($user_manager, $user_dao, $storage);

        $command_tester = new CommandTester($command);
        $command_tester->execute([]);

        self::assertEquals(0, $command_tester->getStatusCode());
        self::assertFileDoesNotExist($user_102_avatar_file_path);
        self::assertTrue($storage->hasBeenDeleted());
    }
}
