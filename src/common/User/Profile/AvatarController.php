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
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;
use UserManager;

class AvatarController implements DispatchableWithRequest, DispatchableWithRequestNoAuthz
{
    const DEFAULT_AVATAR = __DIR__ . '/../../../www/themes/common/images/avatar_default.png';

    private $never_expires = false;

    const ONE_YEAR_IN_SECONDS = 3600 * 24 * 365;

    public function __construct(array $options = [])
    {
        if (isset($options['expires']) && $options['expires'] === 'never') {
            $this->never_expires = true;
        }
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = UserManager::instance()->getUserByUserName($variables['name']);
        if ($user === null) {
            throw new NotFoundException(_("That user does not exist."));
        }

        if ($user->hasAvatar()) {
            $user_avatar_path = $user->getAvatarFilePath();
            if (is_file($user_avatar_path)) {
                if (isset($variables['hash'])) {
                    $this->redirectIfStalled($layout, $user_avatar_path, $variables['hash'], $variables['name']);
                }
                $this->displayAvatar($user_avatar_path);
                return;
            }
        }

        $this->displayAvatar(self::DEFAULT_AVATAR);
    }

    private function redirectIfStalled(BaseLayout $layout, $user_avatar_path, $hash, $user_name)
    {
        $current_hash = hash_file('sha256', $user_avatar_path);
        if ($current_hash !== $hash) {
            $layout->permanentRedirect('/users/'.$user_name.'/avatar-'.$current_hash.'.png');
        }
    }

    private function displayAvatar($path)
    {
        header('Content-Type: image/png');
        if ($this->never_expires) {
            header('Cache-Control: max-age='.self::ONE_YEAR_IN_SECONDS);
        } else {
            header('Cache-Control: max-age=60');
        }
        readfile($path);
    }

    /**
     * Avatar is a public information for all authenticated users
     *
     * @param \URLVerification $url_verification
     * @param \HTTPRequest $request
     * @param array $variables
     *
     * @return boolean Whether access is granted or not
     */
    public function userCanAccess(\URLVerification $url_verification, \HTTPRequest $request, array $variables)
    {
        if (! ForgeConfig::areAnonymousAllowed() && $request->getCurrentUser()->isAnonymous()) {
            return false;
        }
        return true;
    }
}
