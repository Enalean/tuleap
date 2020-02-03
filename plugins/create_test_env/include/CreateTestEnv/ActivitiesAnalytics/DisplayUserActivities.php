<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\CreateTestEnv\ActivitiesAnalytics;

use User_ForgeUserGroupPermission;

class DisplayUserActivities extends User_ForgeUserGroupPermission
{
    public const ID = 10;

    public function getId()
    {
        return self::ID;
    }

    public function getName()
    {
        return dgettext('tuleap-create_test_env', 'Access create test environment activities');
    }

    public function getDescription()
    {
        return dgettext('tuleap-create_test_env', 'Access create test environment activities');
    }
}
