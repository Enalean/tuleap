<?php
/**
  * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class ForgeAccess {
    const PROJECT_ADMIN_CAN_CHOOSE_VISIBILITY = 'project_admin_can_choose_visibility';
    const SUPER_PUBLIC_PROJECTS               = 'super_public_projects';

    const ANONYMOUS_CAN_SEE_SITE_HOMEPAGE = 'anonymous_can_see_site_homepage';
    const ANONYMOUS_CAN_SEE_CONTACT       = 'anonymous_can_see_contact';

    const CONFIG     = 'access_mode';
    const ANONYMOUS  = 'anonymous';
    const REGULAR    = 'regular';
    const RESTRICTED = 'restricted';
}