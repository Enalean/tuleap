<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST;

use Tuleap\REST\JsonCast;
use Tuleap\Tracker\FormElement\TrackerFormElement;

/**
 * @psalm-immutable
 */
class MinimalFieldRepresentation
{
    /**
     * @var int
     */
    public $field_id;

    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $name;

    public function __construct(TrackerFormElement $field)
    {
        $this->field_id = JsonCast::toInt($field->getId());
        $this->name     = $field->getName();
        $this->label    = $field->getLabel();
    }
}
