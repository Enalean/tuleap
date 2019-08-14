<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement\Burnup;

use ForgeConfig;
use Tracker_Artifact;

class CountElementsModeChecker
{
    public function burnupMustUseCountElementsMode(Tracker_Artifact $artifact): bool
    {
        if (! ForgeConfig::get('use_burnup_count_elements')) {
            return false;
        }

        $burnup_ids = ForgeConfig::get('burnup_ids_count_elements', []);

        foreach (explode(',', $burnup_ids) as $burnup_id) {
            if ((int) $burnup_id === (int) $artifact->getId()) {
                return true;
            }
        }

        return false;
    }
}
