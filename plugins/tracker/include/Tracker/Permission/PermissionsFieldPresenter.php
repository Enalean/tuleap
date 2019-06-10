<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Permission;

class PermissionsFieldPresenter
{
    /**
     * @var int
     */
    public $field_id;
    /**
     * @var string
     */
    public $field_name;
    /**
     * @var int
     */
    public $ugroup_id;
    /**
     * @var bool
     */
    public $has_submit_access;
    /**
     * @var bool
     */
    public $not_submitable;
    /**
     * @var bool
     */
    public $is_first = false;
    /**
     * @var bool
     */
    public $is_updatable;
    /**
     * @var bool
     */
    public $has_no_access;
    /**
     * @var bool
     */
    public $has_read_access;
    /**
     * @var bool
     */
    public $has_update_access;

    public function __construct(int $field_id, string $field_name, int $ugroup_id, bool $is_checked, bool $can_submit, bool $can_update, bool $has_no_access, bool $has_read_access, bool $has_update_access)
    {
        $this->field_id          = $field_id;
        $this->field_name        = $field_name;
        $this->ugroup_id         = $ugroup_id;
        $this->has_submit_access = $is_checked;
        $this->not_submitable    = ! $can_submit;
        $this->is_updatable      = $can_update;
        $this->has_no_access     = $has_no_access;
        $this->has_read_access   = $has_read_access;
        $this->has_update_access = $has_update_access;
    }
}
