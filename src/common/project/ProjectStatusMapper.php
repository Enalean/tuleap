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

namespace Tuleap\Project;

use Project;

class ProjectStatusMapper
{
    const STATUS_FLAGS = [
        Project::STATUS_DELETED_LABEL,
        Project::STATUS_PENDING_LABEL,
        Project::STATUS_ACTIVE_LABEL,
        Project::STATUS_SUSPENDED_LABEL,
        Project::STATUS_INCOMPLETE_LABEL,
        Project::STATUS_SYSTEM_LABEL
    ];

    const LABEL_TO_FLAG_MAP = [
        Project::STATUS_DELETED_LABEL    => Project::STATUS_DELETED,
        Project::STATUS_PENDING_LABEL    => Project::STATUS_PENDING,
        Project::STATUS_ACTIVE_LABEL     => Project::STATUS_ACTIVE,
        Project::STATUS_SUSPENDED_LABEL  => Project::STATUS_SUSPENDED,
        Project::STATUS_INCOMPLETE_LABEL => Project::STATUS_INCOMPLETE,
        Project::STATUS_SYSTEM_LABEL     => Project::STATUS_SYSTEM
    ];

    public static function isValidProjectStatusLabel($status_label)
    {
        return in_array($status_label, self::STATUS_FLAGS);
    }

    public static function getProjectStatusFlagFromStatusLabel($status_label)
    {
        if (! array_key_exists($status_label, self::LABEL_TO_FLAG_MAP)) {
            return null;
        }

        return self::LABEL_TO_FLAG_MAP[$status_label];
    }

    public static function getProjectStatusLabelFromStatusFlag($status_flag)
    {
        $flag_to_label_map = array_flip(self::LABEL_TO_FLAG_MAP);

        if (! array_key_exists($status_flag, $flag_to_label_map)) {
            return null;
        }

        return $flag_to_label_map[$status_flag];
    }
}
