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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @psalm-immutable
 */
class FlamingParrot_SearchFormPresenter
{
    /**
     * @var string
     */
    public $selected_entry_value;

    /**
     * @var array
     */
    public $hidden_fields;

    public function __construct(string $selected_entry_value, array $hidden_fields)
    {
        $this->selected_entry_value = $selected_entry_value;
        $this->hidden_fields        = $hidden_fields;
    }
}
