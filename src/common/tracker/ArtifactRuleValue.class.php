<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

 require_once('ArtifactRule.class.php');

/**
* RuleValue  between two dynamic fields
*
* For a tracker, if a source field is selected to a specific value,
* then target field will propose a value.
*
*/
class ArtifactRuleValue extends ArtifactRule
{

    public $target_value;

    public function __construct($id, $group_artifact_id, $source_field, $source_value, $target_field, $target_value)
    {
        parent::__construct($id, $group_artifact_id, $source_field, $source_value, $target_field);
        $this->target_value = $target_value;
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
    public function applyTo($group_artifact_id, $source_field, $source_value, $target_field, $target_value)
    {
        $can_apply_to = $this->canApplyTo($group_artifact_id, $source_field, $source_value, $target_field, $target_value);
        $pass = $can_apply_to && $target_value == $this->target_value;
        return $pass;
    }

    public function canApplyTo($group_artifact_id, $source_field, $source_value, $target_field, $target_value)
    {
        $match = $group_artifact_id == $this->group_artifact_id &&
            $source_field == $this->source_field &&
            $source_value == $this->source_value &&
            $target_field == $this->target_field;
        return $match;
    }

    public function getTargetValueId()
    {
        return $this->target_value;
    }
}
