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

namespace Tuleap\User\Profile;

use ForgeConfig;
use HTTPRequest;
use PFUser;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use UserManager;

class AvatarController implements DispatchableWithRequest
{
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user                = UserManager::instance()->getUserByUserName($variables['name']);
        $default_avatar_path = ForgeConfig::get('sys_urlroot') . '/themes/common/images/avatar_default.png';

        if (! $user || ! $user->hasAvatar()) {
            $this->displayAvatar($default_avatar_path);
            return;
        }

        $user_avatar_path = $this->getUserAvatarPath($user);
        if (! is_file($user_avatar_path)) {
            $this->displayAvatar($default_avatar_path);
            return;
        }

        $this->displayAvatar($user_avatar_path);
    }

    private function displayAvatar($path)
    {
        header('Contenttype: image/png');
        header("CacheControl: nocache, mustrevalidate");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        readfile($path);
    }

    /**
     * @param $user_id
     *
     * @return string
     */
    private function getUserAvatarPath(PFUser $user)
    {
        $user_id     = $user->getId();
        $avatar_path = ForgeConfig::get('sys_avatar_path', ForgeConfig::get('sys_data_dir') . '/user/avatar/');
        return $path = $avatar_path . DIRECTORY_SEPARATOR .
            substr($user_id, -2, 1) . DIRECTORY_SEPARATOR . substr($user_id, -1, 1) .
            DIRECTORY_SEPARATOR . $user_id . DIRECTORY_SEPARATOR . 'avatar';
    }
}
