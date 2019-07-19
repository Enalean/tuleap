<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Admin\ProjectUGroup\Details;

class MembersPresenter
{
    /**
     * @var array
     */
    public $members;
    /**
     * @var bool
     */
    public $has_members;
    /**
     * @var bool
     */
    public $can_be_updated;
    /**
     * @var bool
     */
    public $is_dynamic_group;
    /**
     * @var bool
     */
    public $is_synchronized_message_shown;

    public function __construct(
        array $members,
        bool $can_be_updated,
        bool $is_dynamic_group,
        bool $is_synchronized_with_project_members
    ) {
        $this->has_members                   = count($members) > 0;
        $this->can_be_updated                = $can_be_updated;
        $this->members                       = $members;
        $this->is_dynamic_group              = $is_dynamic_group;
        $this->is_synchronized_message_shown = $is_synchronized_with_project_members && $can_be_updated;
    }
}
