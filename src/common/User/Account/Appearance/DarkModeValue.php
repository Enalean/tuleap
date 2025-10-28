<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\User\Account\Appearance;

use PFUser;
use Tuleap\User\Account\DarkMode;

enum DarkModeValue: string
{
    case Light  = 'light';
    case Dark   = 'dark';
    case System = 'system';

    public static function fromUser(PFUser $user): self
    {
        $pref_value = $user->getPreference(DarkMode::PREFERENCE_DARK_MODE);

        if (! is_string($pref_value)) {
            return self::default();
        }

        $valid_dark_mode_value = self::tryFrom($pref_value);

        if ($valid_dark_mode_value === null) {
            return self::default();
        }

        return $valid_dark_mode_value;
    }

    public static function default(): self
    {
        return self::Light;
    }
}
