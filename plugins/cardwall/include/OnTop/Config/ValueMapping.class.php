<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

class Cardwall_OnTop_Config_ValueMapping
{

    /**
     * @var Tracker_FormElement_Field_List_Value
     */
    private $value;

    /**
     * @var int
     */
    private $column_id;

    public function __construct(Tracker_FormElement_Field_List_Value $value, $column_id)
    {
        $this->value     = $value;
        $this->column_id = $column_id;
    }

    public function getValueId()
    {
        return $this->value->getId();
    }

    public function getXMLValueId()
    {
        return $this->value->getXMLId();
    }

    public function getValueLabel()
    {
        return $this->value->getLabel();
    }

    /**
     * @return Tracker_FormElement_Field_List_Value
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getColumnId()
    {
        return $this->column_id;
    }

    /**
     * Return true is the given status label match the current value
     *
     * @param string|null $artifact_status_label
     *
     * @return bool
     */
    public function matchStatusLabel($artifact_status_label)
    {
        return $this->matchLabel($artifact_status_label) || $this->matchNone($artifact_status_label);
    }

    private function matchLabel($artifact_status_label)
    {
        return $this->getValueLabel() == $artifact_status_label;
    }

    private function matchNone($artifact_status_label)
    {
        return $artifact_status_label === null && $this->getValueId() == 100;
    }
}
