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

namespace Tuleap\Statistics\DiskUsagePie;

class DiskUsagePieMountPointPresenter
{
    /**
     * @var string
     */
    public $disk_usage;

    public function __construct(
        $remaining_space,
        $used_proportion,
        $human_readable_remaining_space,
        $human_readable_usage
    ) {
        $this->disk_usage = json_encode([
            [
                "count" => $used_proportion,
                "key"   => "used",
                "label" => dgettext('tuleap-statistics', 'Used proportion'),
                "value" => $human_readable_usage
            ], [
                "count" => $remaining_space,
                "key"   => "remaining",
                "label" => dgettext('tuleap-statistics', 'Remaining space'),
                "value" => $human_readable_remaining_space
            ]
        ]);
    }
}
