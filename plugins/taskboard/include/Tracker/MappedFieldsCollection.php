<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Tracker;

use Tracker;
use Tracker_FormElement_Field_Selectbox;

class MappedFieldsCollection
{
    /**
     * @psalm-var array<int, Tracker_FormElement_Field_Selectbox>
     */
    private $mapped_fields;

    /**
     * @psalm-param array<int, Tracker_FormElement_Field_Selectbox> $mapped_fields
     * @param Tracker_FormElement_Field_Selectbox[] $mapped_fields
     */
    public function __construct(array $mapped_fields = [])
    {
        $this->mapped_fields = $mapped_fields;
    }

    public function put(Tracker $tracker, Tracker_FormElement_Field_Selectbox $field): void
    {
        $this->mapped_fields[$tracker->getId()] = $field;
    }

    public function get(Tracker $tracker): Tracker_FormElement_Field_Selectbox
    {
        $tracker_id = $tracker->getId();
        if (! $this->hasKey($tracker)) {
            throw new \OutOfBoundsException("There is no mapped field for tracker $tracker_id");
        }

        return $this->mapped_fields[$tracker_id];
    }

    public function hasKey(Tracker $tracker): bool
    {
        return isset($this->mapped_fields[$tracker->getId()]);
    }
}
