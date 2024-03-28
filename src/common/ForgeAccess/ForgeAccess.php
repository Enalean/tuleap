<?php
/**
  * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

declare(strict_types=1);

use Tuleap\Config\ConfigKey;

class ForgeAccess
{
    public const SUPER_PUBLIC_PROJECTS = 'super_public_projects';

    #[ConfigKey('Set to 1 if anonymous users should be allowed to site site home page')]
    public const ANONYMOUS_CAN_SEE_SITE_HOMEPAGE = 'anonymous_can_see_site_homepage';

    #[ConfigKey('Set to 1 if anonymous users should be allowed to see Contact page')]
    public const ANONYMOUS_CAN_SEE_CONTACT = 'anonymous_can_see_contact';

    public const CONFIG     = 'access_mode';
    public const ANONYMOUS  = 'anonymous';
    public const REGULAR    = 'regular';
    public const RESTRICTED = 'restricted';

    public function doesPlatformRequireLogin(): bool
    {
        if (ForgeConfig::areAnonymousAllowed()) {
            return false;
        }

        return true;
    }

    /**
     * @return \ForgeAccess::ANONYMOUS|\ForgeAccess::REGULAR|\ForgeAccess::RESTRICTED
     */
    public static function getAccessMode(): string
    {
        return \ForgeConfig::get(self::CONFIG, self::RESTRICTED);
    }
}
