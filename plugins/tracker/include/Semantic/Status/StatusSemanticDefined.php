<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Status;

use Tracker_FormElement_Field_List;

/**
 * @psalm-immutable
 */
class StatusSemanticDefined
{
    /**
     * @var Tracker_FormElement_Field_List
     */
    private $field;
    /**
     * @var int[]
     */
    private $open_values;

    public function __construct(Tracker_FormElement_Field_List $field, array $open_values)
    {
        $this->field       = $field;
        $this->open_values = $open_values;
    }

    public function getField(): Tracker_FormElement_Field_List
    {
        return $this->field;
    }

    /**
     * @return int[]
     */
    public function getOpenValues(): array
    {
        return $this->open_values;
    }
}
