<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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


abstract class Cardwall_OnTop_Config_TrackerMapping
{

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var array
     */
    private $available_fields;

    public function __construct(Tracker $tracker, array $available_fields)
    {
        $this->tracker          = $tracker;
        $this->available_fields = $available_fields;
    }

    /**
     * @return Tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    public function getAvailableFields()
    {
        return $this->available_fields;
    }

    /**
     * Return true of the given status label belongs to the given column
     *
     * @param string|null $artifact_status
     *
     * @return bool
     */
    public function isMappedTo(Cardwall_Column $column, $artifact_status)
    {
        foreach ($this->getValueMappings() as $value_mapping) {
            if ($value_mapping->matchStatusLabel($artifact_status)) {
                return $value_mapping->getColumnId() == $column->getId();
            }
        }
        return false;
    }

    /**
     * @return Tracker_FormElement|null
     */
    abstract public function getField();

    /**
     * @return Cardwall_OnTop_Config_ValueMapping[]
     */
    abstract public function getValueMappings();

    /**
     * @pattern Visitor
     */
    abstract public function accept($visitor);

    public function isCustom()
    {
        return false;
    }
}
