<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations;

use PFUser;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValueRepresentation;
use Tuleap\ServerHostname;
use UserHelper;

/**
 * @psalm-immutable
 */
final readonly class UserRepresentation implements SelectedValueRepresentation
{
    public function __construct(
        public string $display_name,
        public string $avatar_url,
        public ?string $user_url,
        public bool $is_anonymous,
    ) {
    }

    public static function fromPFUser(PFUser $user, UserHelper $helper): self
    {
        return new self(
            self::getDisplayName($user, $helper),
            $user->getAvatarUrl(),
            $user->getPublicProfileUrl(),
            false,
        );
    }

    public static function fromAnonymous(string $name): self
    {
        return new self(
            $name,
            ServerHostname::HTTPSUrl() . PFUser::DEFAULT_AVATAR_URL,
            null,
            true,
        );
    }

    private static function getDisplayName(PFUser $user, UserHelper $helper): string
    {
        if (! $user->isAnonymous()) {
            return $helper->getDisplayNameFromUser($user) ?? '';
        }

        $email = $user->getEmail();
        if ($email !== null) {
            return $email;
        }

        return dgettext('tuleap-crosstracker', 'Anonymous user');
    }
}
