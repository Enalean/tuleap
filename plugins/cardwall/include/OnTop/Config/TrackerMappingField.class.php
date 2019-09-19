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


abstract class Cardwall_OnTop_Config_TrackerMappingField extends Cardwall_OnTop_Config_TrackerMapping
{

    /**
     * @var Tracker_FormElement_Field
     */
    private $field;

    /**
     * @var array of Cardwall_OnTop_Config_ValueMapping
     */
    private $value_mappings;

    public function __construct(Tracker $tracker, array $available_fields, array $value_mappings, Tracker_FormElement_Field $field)
    {
        parent::__construct($tracker, $available_fields);
        $this->value_mappings   = $value_mappings;
        $this->field            = $field;
    }

    public function getField()
    {
        return $this->field;
    }

    /**
     * @return array of Cardwall_OnTop_Config_ValueMapping
     */
    public function getValueMappings()
    {
        return $this->value_mappings;
    }

    /**
     * @return string
     */
    public function getSelectedValueLabel(Cardwall_Column $column, $default = '')
    {
        foreach ($this->value_mappings as $mapping) {
            if ($mapping->getColumnId() == $column->getId()) {
                return $mapping->getValue()->getLabel();
            }
        }
        return $default;
    }
}
