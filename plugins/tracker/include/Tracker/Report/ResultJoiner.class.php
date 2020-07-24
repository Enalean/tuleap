<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Join results
 *
 * The report returns a set of matching ids S1.
 * The plugin agiledashboard performed an additional query that returned another set S2
 * etc.
 *
 * => The remaining result set is the intersection S = S1 ∩ S2 ∩ S3
 */
class Tracker_Report_ResultJoiner
{

    public function joinResults(array $matching_ids, array $other_results)
    {
        return call_user_func_array(
            'array_intersect_key',
            array_merge([$matching_ids], $other_results)
        );
    }
}
