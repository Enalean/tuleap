<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class UserAvatarSaverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public const MINIMAL_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACklEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg==';

    public function testANewAvatarCanBeUploaded()
    {
        $filesystem   = vfsStream::setup();
        $user_manager = \Mockery::mock(\UserManager::class);

        $user_avatar_saver = new UserAvatarSaver($user_manager);

        $user             = \Mockery::mock(\PFUser::class);
        $avatar_file_path = $filesystem->url() . '/folder/user/avatar';
        $user->shouldReceive('getAvatarFilePath')->andReturns($avatar_file_path);

        $user->shouldReceive('setHasCustomAvatar')->with(true)->once();
        $user_manager->shouldReceive('updateDb')->once();

        $avatar_temporary_path = $filesystem->url() . '/avatar_tmp_upload';
        file_put_contents($avatar_temporary_path, base64_decode(self::MINIMAL_PNG_BASE64));

        $user_avatar_saver->saveAvatar($user, $avatar_temporary_path);

        $this->assertFileExists($avatar_file_path);
    }
}
