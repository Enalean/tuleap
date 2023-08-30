<?php
/**
* Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow;

class WorkflowMenuTabPresenter
{
    public readonly string $used_services_names;

    public function __construct(
        public readonly array $tabs_menu,
        public readonly int $tracker_id,
        array $used_services_names,
        public readonly bool $is_split_feature_flag_enabled,
    ) {
        $this->used_services_names = json_encode($used_services_names);
    }
}
