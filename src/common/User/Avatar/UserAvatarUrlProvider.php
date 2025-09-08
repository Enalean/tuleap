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

use Tuleap\Option\Option;

final readonly class UserAvatarUrlProvider implements ProvideUserAvatarUrl, ProvideDefaultUserAvatarUrl
{
    public function __construct(private AvatarHashStorage $storage, private ComputeAvatarHash $compute_avatar_hash)
    {
    }

    #[\Override]
    public function getAvatarUrl(\PFUser $user): string
    {
        if ($user->isAnonymous() || ! $user->hasAvatar()) {
            return $this->getDefaultAvatarUrl();
        }

        return \Tuleap\ServerHostname::HTTPSUrl() . $this->getAbsoluteUrl($user);
    }

    #[\Override]
    public function getDefaultAvatarUrl(): string
    {
        return \Tuleap\ServerHostname::HTTPSUrl() . \PFUser::DEFAULT_AVATAR_URL;
    }

    private function getAbsoluteUrl(\PFUser $user): string
    {
        return $this->getAvatarFileHash($user)
            ->match(
                static fn(string $hash) => '/users/' . urlencode($user->getUserName()) . '/avatar-' . $hash . '.png',
                static fn() => '/users/' . urlencode($user->getUserName()) . '/avatar.png',
            );
    }

    /**
     * @return Option<string>
     */
    private function getAvatarFileHash(\PFUser $user): Option
    {
        return $this->storage
            ->retrieve($user)
            ->orElse(function () use ($user) {
                $avatar_file_path = $user->getAvatarFilePath();
                if (! is_file($avatar_file_path)) {
                    return Option::nothing(\Psl\Type\string());
                }

                $hash = $this->compute_avatar_hash->computeAvatarHash($avatar_file_path);
                $this->storage->store($user, $hash);

                return Option::fromValue($hash);
            });
    }
}
