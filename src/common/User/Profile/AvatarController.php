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

namespace Tuleap\User\Profile;

use ForgeConfig;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Option\Option;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\User\Avatar\AvatarHashStorage;
use Tuleap\User\Avatar\ComputeAvatarHash;
use UserManager;

class AvatarController implements DispatchableWithRequest, DispatchableWithRequestNoAuthz
{
    public const DEFAULT_AVATAR = __DIR__ . '/../../../www/themes/common/images/avatar_default.png';

    private $never_expires = false;

    public const ONE_YEAR_IN_SECONDS = 3600 * 24 * 365;
    /**
     * @var AvatarGenerator
     */
    private $avatar_generator;

    public function __construct(
        AvatarGenerator $avatar_generator,
        private AvatarHashStorage $avatar_hash_storage,
        private ComputeAvatarHash $compute_avatar_hash,
        array $options = [],
    ) {
        if (isset($options['expires']) && $options['expires'] === 'never') {
            $this->never_expires = true;
        }
        $this->avatar_generator = $avatar_generator;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        // Avatar is a public information for all authenticated users
        if (! ForgeConfig::areAnonymousAllowed() && $request->getCurrentUser()->isAnonymous()) {
            $this->displayDefaultAvatarAsError();
        }

        $user_manager = UserManager::instance();
        $user         = $user_manager->getUserByUserName($variables['name']);
        if ($user === null) {
            $this->displayDefaultAvatarAsError();
        }

        if ($user->hasAvatar()) {
            $user_avatar_path = $user->getAvatarFilePath();
            if (! is_file($user_avatar_path)) {
                $this->avatar_generator->generate($user, $user_avatar_path);
                $user->setHasCustomAvatar(false);
                $user_manager->updateDb($user);
            }

            if (is_file($user_avatar_path)) {
                if (isset($variables['hash'])) {
                    $this->redirectIfStalled($layout, $user, $variables['hash'], $variables['name']);
                }
                $this->displayAvatar($user_avatar_path);
                return;
            }
        }

        $this->displayAvatar(self::DEFAULT_AVATAR);
    }

    private function redirectIfStalled(BaseLayout $layout, \PFUser $user, $hash, $user_name)
    {
        $this->avatar_hash_storage
            ->retrieve($user)
            ->orElse(function () use ($user) {
                return Option::fromValue(
                    $this->compute_avatar_hash->computeAvatarHash($user->getAvatarFilePath())
                );
            })->andThen(function (string $current_hash) use ($layout, $hash, $user_name) {
                if ($current_hash !== $hash) {
                    $layout->permanentRedirect('/users/' . $user_name . '/avatar-' . $current_hash . '.png');
                }

                return Option::fromValue(null);
            });
    }

    private function displayAvatar($path)
    {
        header('Content-Type: image/png');
        if ($this->never_expires) {
            header('Cache-Control: max-age=' . self::ONE_YEAR_IN_SECONDS . ',immutable');
        } else {
            header('Cache-Control: max-age=60');
        }
        readfile($path);
    }

    private function displayDefaultAvatarAsError(): void
    {
        http_response_code(404);
        header('Content-Type: image/png');
        header('Cache-Control: max-age=60');
        readfile(self::DEFAULT_AVATAR);
        exit;
    }
}
