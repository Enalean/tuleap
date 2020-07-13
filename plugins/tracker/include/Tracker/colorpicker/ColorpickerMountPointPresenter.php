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

namespace Tuleap\Tracker\Colorpicker;

/**
 * @psalm-immutable
 */
class ColorpickerMountPointPresenter
{
    /**
     * @var string
     */
    public $input_name;

    /**
     * @var string
     */
    public $input_id;

    /**
     * @var bool
     */
    public $is_switch_disabled;

    /**
     * @var string
     */
    public $current_color;

    public function __construct(
        $current_color,
        $field_name,
        $field_id,
        $is_field_used_in_semantic
    ) {
        $this->current_color      = ($current_color) ? $current_color : '';
        $this->input_name         = $field_name;
        $this->input_id           = $field_id;
        $this->is_switch_disabled = $is_field_used_in_semantic;
    }
}
