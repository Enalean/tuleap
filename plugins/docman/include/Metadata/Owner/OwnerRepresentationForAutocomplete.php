<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\Metadata\Owner;

use PFUser;

/**
 * @psalm-immutable
 */
final class OwnerRepresentationForAutocomplete
{
    /**
     * @var string The text we want to display in the select2
     */
    public string $text;

    private function __construct(
        public int $tuleap_user_id,
        private string $display_name,
        public string $avatar_url,
        public bool $has_avatar,
    ) {
        $this->text = $this->display_name;
    }

    public static function buildForSelect2AutocompleteFromOwner(PFUser $user): self
    {
        $owner_display_name = $user->getRealName() . ' (' . $user->getUserName() . ')';
        return new OwnerRepresentationForAutocomplete((int) $user->getId(), $owner_display_name, $user->getAvatarUrl(), $user->hasAvatar());
    }
}
