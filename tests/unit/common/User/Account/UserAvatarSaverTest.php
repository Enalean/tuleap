<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\User\Account;

use org\bovigo\vfs\vfsStream;
use Tuleap\Test\Stubs\User\Avatar\AvatarHashStorageStub;
use Tuleap\User\Avatar\ComputeAvatarHash;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserAvatarSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public const MINIMAL_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACklEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg==';

    public function testANewAvatarCanBeUploaded(): void
    {
        $filesystem   = vfsStream::setup();
        $user_manager = $this->createMock(\UserManager::class);

        $avatar_hash_storage = AvatarHashStorageStub::withoutStoredHash();
        $compute_avatar_hash = new ComputeAvatarHash();

        $user_avatar_saver = new UserAvatarSaver($user_manager, $avatar_hash_storage, $compute_avatar_hash);

        $user             = $this->createMock(\PFUser::class);
        $avatar_file_path = $filesystem->url() . '/folder/user/avatar';
        $user->method('getAvatarFilePath')->willReturn($avatar_file_path);

        $user->expects($this->once())->method('setHasCustomAvatar')->with(true);
        $user_manager->expects($this->once())->method('updateDb');

        $avatar_temporary_path = $filesystem->url() . '/avatar_tmp_upload';
        file_put_contents($avatar_temporary_path, base64_decode(self::MINIMAL_PNG_BASE64));

        $user_avatar_saver->saveAvatar($user, $avatar_temporary_path);

        self::assertFileExists($avatar_file_path);
        self::assertSame(
            $compute_avatar_hash->computeAvatarHash($avatar_file_path),
            $avatar_hash_storage->getNewStoredHash(),
        );
    }
}
