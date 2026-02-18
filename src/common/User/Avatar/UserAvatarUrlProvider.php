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

final readonly class UserAvatarUrlProvider implements ProvideUserAvatarUrl, ProvideDefaultUserAvatarUrl
{
    public function __construct(private AvatarHashStorage $storage)
    {
    }

    #[\Override]
    public function getAvatarUrl(\PFUser $user): string
    {
        return $this->getAvatarUrls($user)[0]?->avatar_url ?? $this->getDefaultAvatarUrl();
    }

    #[\Override]
    public function getAvatarUrls(\PFUser ...$users): array
    {
        $avatar_urls = [];

        $base_server_url = \Tuleap\ServerHostname::HTTPSUrl();

        $user_avatar_hashes = $this->storage->retrieveHashes(...$users);
        foreach ($user_avatar_hashes as $user_avatar_hash) {
            $avatar_urls[] = new UserAvatarUrl(
                $user_avatar_hash->user,
                $base_server_url . $this->getAbsoluteUrl($user_avatar_hash),
            );
        }

        return $avatar_urls;
    }

    #[\Override]
    public function getDefaultAvatarUrl(): string
    {
        return \Tuleap\ServerHostname::HTTPSUrl() . \PFUser::DEFAULT_AVATAR_URL;
    }

    private function getAbsoluteUrl(UserAvatarHash $user_avatar_hash): string
    {
        $user = $user_avatar_hash->user;
        if ($user->isAnonymous() || ! $user->hasAvatar()) {
            return \PFUser::DEFAULT_AVATAR_URL;
        }

        return $user_avatar_hash->avatar_hash
            ->match(
                static fn(string $hash) => '/users/' . urlencode($user->getUserName()) . '/avatar-' . $hash . '.png',
                static fn() => '/users/' . urlencode($user->getUserName()) . '/avatar.png',
            );
    }
}
