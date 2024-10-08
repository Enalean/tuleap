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

use Gumlet\ImageResize;
use Gumlet\ImageResizeException;
use Tuleap\User\Avatar\AvatarHashStorage;
use Tuleap\User\Avatar\ComputeAvatarHash;

final readonly class UserAvatarSaver
{
    public const AVATAR_MAX_SIZE = 100;


    public function __construct(
        private \UserManager $user_manager,
        private AvatarHashStorage $avatar_hash_storage,
        private ComputeAvatarHash $compute_avatar_hash,
    ) {
    }

    /**
     * @throws ImageResizeException
     */
    public function saveAvatar(\PFUser $user, $temporary_path_avatar)
    {
        $image                    = new ImageResize($temporary_path_avatar);
        $image->quality_truecolor = false;
        $image->crop(self::AVATAR_MAX_SIZE, self::AVATAR_MAX_SIZE);
        // Replace transparent background by white color to avoid strange rendering in Tuleap.
        $image->addFilter(function ($imageDesc) {
            $x                = imagesx($imageDesc);
            $y                = imagesy($imageDesc);
            $dst_im           = imagecreatetruecolor($x, $y);
            $background_color = imagecolorallocate($dst_im, 255, 255, 255);
            imagefilledrectangle($dst_im, 0, 0, $x, $y, $background_color);

            imagecopy($dst_im, $imageDesc, 0, 0, 0, 0, $x, $y);

            imagecopy($imageDesc, $dst_im, 0, 0, 0, 0, $x, $y);

            imagedestroy($dst_im);
        });
        $avatar_path   = $user->getAvatarFilePath();
        $avatar_folder = dirname($avatar_path);
        if (! is_dir($avatar_folder) && ! mkdir($avatar_folder, 0777, true) && ! is_dir($avatar_folder)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $avatar_folder));
        }
        $image->save($avatar_path, IMAGETYPE_PNG, 9, 0640);
        $user->setHasCustomAvatar(true);
        $this->avatar_hash_storage->store($user, $this->compute_avatar_hash->computeAvatarHash($avatar_path));
        $this->user_manager->updateDb($user);
    }
}
