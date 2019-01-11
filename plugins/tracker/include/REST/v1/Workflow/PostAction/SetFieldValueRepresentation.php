<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Workflow;

use Tuleap\REST\JsonCast;

/**
 * Representation of a transition action which sets a fixed field value.
 * Build new instance with one of the following methods, depending on the type of the field to set:
 * @see SetFieldValueRepresentation::forDate()
 * @see SetFieldValueRepresentation::forInt()
 * @see SetFieldValueRepresentation::forFloat()
 */
class SetFieldValueRepresentation
{
    /**
     * @var string Action identifier (unique among actions with same type and same field type)
     */
    public $id;

    /**
     * @var string
     */
    public $type = "set_field_value";

    /**
     * @var int
     */
    public $field_id;

    /**
     * @var string date, int or float
     */
    public $field_type;

    /**
     * @var int|float
     */
    public $value;

    private function __construct($id, $field_id, $field_type, $value)
    {
        $this->id = $id;
        $this->field_id = $field_id;
        $this->field_type = $field_type;
        $this->value = $value;
    }

    /**
     * @var string $id Action identifier (unique among actions with same type and same field type)
     * @param int $field_id
     * @param int $value
     * @return SetFieldValueRepresentation
     */
    public static function forDate($id, $field_id, $value)
    {
        return new self(
            JsonCast::toInt($id),
            JsonCast::toInt($field_id),
            'date',
            JsonCast::toInt($value)
        );
    }

    /**
     * @var string $id Action identifier (unique among actions with same type and same field type)
     * @param int $field_id
     * @param int $value
     * @return SetFieldValueRepresentation
     */
    public static function forInt($id, $field_id, $value)
    {
        return new self(
            JsonCast::toInt($id),
            JsonCast::toInt($field_id),
            'int',
            JsonCast::toInt($value)
        );
    }

    /**
     * @var string $id Action identifier (unique among actions with same type and same field type)
     * @param int $field_id
     * @param float $value
     * @return SetFieldValueRepresentation
     */
    public static function forFloat($id, $field_id, $value)
    {
        return new self(
            JsonCast::toInt($id),
            JsonCast::toInt($field_id),
            'float',
            JsonCast::toFloat($value)
        );
    }
}
