<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * A tracker field that can be shared with other trackers.
 *
 * For now, only select boxes and multi-select boxes can be shared.
 */
interface Tracker_FormElement_Field_Shareable
{

    /**
     * Fixes original value ids after field duplication.
     *
     * @param array $value_mapping An array associating old value ids to new value ids.
     */
    public function fixOriginalValueIds(array $value_mapping);
}
