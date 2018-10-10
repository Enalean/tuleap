<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\Repository\View;

use ForgeConfig;
use HTTPRequest;

trait FeatureFlag
{
    private function isTuleapBeauGitActivated()
    {
        $whitelist = ForgeConfig::get('sys_project_whitelist_that_should_use_deprecated_git_interface');
        if (empty($whitelist)) {
            return true;
        }

        $current_project_id = (int) HTTPRequest::instance()->getProject()->getID();
        foreach (explode(',', $whitelist) as $whitelisted_project_id) {
            if ($current_project_id === (int) $whitelisted_project_id) {
                return false;
            }
        }

        return true;
    }
}
