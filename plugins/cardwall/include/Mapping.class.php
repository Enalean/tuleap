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
 * Mapping (column_is, field_id, value_id)
 *
 * Each artifact, must send its own values id depending on its status field
 * and the column it has been dropped into.
 */
class Cardwall_Mapping
{

    /**
     * @var int
     */
    public $column_id;

    /**
     * @var int
     */
    public $field_id;

    /**
     * @var int
     */
    public $value_id;

    public function __construct($column_id, $field_id, $value_id)
    {
        $this->column_id = $column_id;
        $this->field_id  = $field_id;
        $this->value_id  = $value_id;
    }
}
