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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Tracker_Reference extends Reference
{

    /**
     * @return Reference
     */
    public function __construct(Tracker $tracker, $keyword)
    {
        $base_id    = 0;
        $visibility = 'P';
        $is_used    = 1;

        parent::__construct(
            $base_id,
            $keyword,
            $GLOBALS['Language']->getText('project_reference', 'reference_art_desc_key') . ' - ' . $tracker->getName(),
            TRACKER_BASE_URL . '/?aid=$1&group_id=$group_id',
            $visibility,
            trackerPlugin::SERVICE_SHORTNAME,
            Tracker_Artifact::REFERENCE_NATURE,
            $is_used,
            $tracker->getGroupId()
        );
    }
}
