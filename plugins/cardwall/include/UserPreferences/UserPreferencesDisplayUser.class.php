<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

class Cardwall_UserPreferences_UserPreferencesDisplayUser implements Tracker_CardDisplayPreferences
{

    public const ASSIGNED_TO_USERNAME_PREFERENCE_NAME = 'AD_cardwall_assign_to_display_username_';

    public const DISPLAY_USERNAMES                    = 0;
    public const DISPLAY_AVATARS                      = 1;

    /** @var bool */
    private $should_display_avatars;

    public function __construct($should_display_avatars)
    {
        $this->should_display_avatars = $should_display_avatars;
    }

    public function shouldDisplayAvatars()
    {
        return $this->should_display_avatars;
    }
}
