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
 * Collection of Cardwall_Mapping
 */
class Cardwall_MappingCollection implements IteratorAggregate
{

    /**
     * @var array of Cardwall_Mapping
     */
    private $mappings = [];

    /**
     * @var array of array of Cardwall_Mapping indexed by field id
     */
    private $mappings_by_field_id = [];

    /**
     * @return Iterator
     */
    public function getIterator()
    {
        return new ArrayObject($this->mappings);
    }

    public function add(Cardwall_Mapping $mapping)
    {
        $this->mappings[]                                 = $mapping;
        $this->mappings_by_field_id[$mapping->field_id][] = $mapping;
    }


    /**
     * Returns the list of static field values of the swimline
     *
     * @param int $field_id
     * @return array of int
     */
    public function getSwimLineValues($field_id)
    {
        $swim_line_values = [];
        foreach ($this->getMappingsByFieldId($field_id) as $mapping) {
            $swim_line_values[] = $mapping->column_id;
        }
        return $swim_line_values;
    }

    /**
     * @return array of Cardwall_Mapping
     */
    private function getMappingsByFieldId($field_id)
    {
        if (isset($this->mappings_by_field_id[$field_id])) {
            return $this->mappings_by_field_id[$field_id];
        }
        return [];
    }
}
