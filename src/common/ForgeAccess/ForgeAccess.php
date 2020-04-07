<?php
/**
  * Copyright (c) Enalean, 2015 - 2019. All Rights Reserved.
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

class ForgeAccess
{
    public const SUPER_PUBLIC_PROJECTS = 'super_public_projects';

    public const ANONYMOUS_CAN_SEE_SITE_HOMEPAGE = 'anonymous_can_see_site_homepage';
    public const ANONYMOUS_CAN_SEE_CONTACT       = 'anonymous_can_see_contact';

    public const CONFIG     = 'access_mode';
    public const ANONYMOUS  = 'anonymous';
    public const REGULAR    = 'regular';
    public const RESTRICTED = 'restricted';

    /**
     * @var PermissionsOverrider_PermissionsOverriderManager
     */
    private $permissions_overrider_manager;

    public function __construct(PermissionsOverrider_PermissionsOverriderManager $permissions_overrider_manager)
    {
        $this->permissions_overrider_manager = $permissions_overrider_manager;
    }

    public function doesPlatformRequireLogin(): bool
    {
        if (ForgeConfig::areAnonymousAllowed() && ! $this->permissions_overrider_manager->doesOverriderForceUsageOfAnonymous()) {
            return false;
        }

        $anonymous_user = new PFUser(['user_id' => 0]);
        if ($this->permissions_overrider_manager->doesOverriderAllowUserToAccessPlatform($anonymous_user)) {
            return false;
        }

        return true;
    }
}
