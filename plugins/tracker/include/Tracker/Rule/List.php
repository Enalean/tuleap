<?php
/**
  * Copyright (c) Enalean, 2012 - Present. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

/**
* RuleValue  between two dynamic fields
*
* For a tracker, if a source field is selected to a specific value,
* then target field will propose a value.
*
*/
class Tracker_Rule_List extends Tracker_Rule
{
    public $target_value;
    public $source_value;

    public function __construct($id = null, $tracker_id = null, $source_field = null, $source_value = null, $target_field = null, $target_value = null)
    {
        $this->setId($id)
            ->setTrackerId($tracker_id)
            ->setSourceFieldId($source_field)
            ->setTargetFieldId($target_field)
            ->setSourceValue($source_value)
            ->setTargetValue($target_value);
    }

    /**
    * Returns if a rule can be applied to a tuple
    *
    * If parameters are not same tracker, same source field, same source value and
    * same target field, then returns true.
    * Else if params are same target value then returns true,
    * Else returns false.
    *
    * @return bool
    */
    public function applyTo($tracker_id, $source_field, $source_value, $target_field, $target_value)
    {
        $can_apply_to = $this->canApplyTo($tracker_id, $source_field, $source_value, $target_field, $target_value);
        $pass         = $can_apply_to && $target_value == $this->target_value;
        return $pass;
    }

    public function canApplyTo($tracker_id, $source_field, $source_value, $target_field, $target_value)
    {
        $match = $tracker_id == $this->tracker_id &&
            $source_field == $this->source_field &&
            $source_value == $this->source_value &&
            $target_field == $this->target_field;
        return $match;
    }

    /**
     *
     * @return string
     */
    public function getSourceValue()
    {
        return $this->source_value;
    }

    /**
     *
     * @param string $value
     * @return \Tracker_Rule_Date
     */
    public function setSourceValue($value)
    {
        $this->source_value = $value;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getTargetValue()
    {
        return $this->target_value;
    }

    /**
     *
     * @param string $value
     * @return \Tracker_Rule_Date
     */
    public function setTargetValue($value)
    {
        $this->target_value = $value;
        return $this;
    }
}
