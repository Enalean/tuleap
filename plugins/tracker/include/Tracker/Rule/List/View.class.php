<?php
/**
  * Copyright (c) Enalean, 2012. All rights reserved
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
* Basic view for a rule
*
*/
class Tracker_Rule_List_View
{

    /**
     *
     * @var Tracker_Rule_List
     */
    public $rule;

    /**
     *  Tracker_Rule_List_View() - constructor
     *
     *  @param Tracker_Rule_List $artifact_rule object
     */
    public function __construct($rule)
    {
            $this->rule = $rule;
    }

    public function display()
    {
        echo $this->fetch();
    }

    /**
     * @return a representation of an artifact rule
     * #id@tracker_id source_field(source_value) => target_field(target_value_1, target_value_2)
     */
    public function fetch()
    {
        $output  = '#' . $this->rule->id;
        $output .= '@' . $this->rule->tracker_id;
        $output .= ' ' . $this->rule->source_field;
        $output .= '(' . $this->rule->source_value . ') =>';
        $output .= ' ' . $this->rule->target_field;
        $output .= '(' . $this->rule->target_value . ')';
        return $output;
    }

    public function fetchJavascript()
    {
        $output  = '{id:' . (int) $this->rule->id . ', ';
        $output .= 'tracker_id:' . (int) $this->rule->tracker_id . ', ';
        $output .= 'source_field:' . (int) $this->rule->source_field . ', ';
        $output .= 'source_value:' . (int) $this->rule->source_value . ', ';
        $output .= 'target_field:' . (int) $this->rule->target_field . ', ';
        $output .= 'target_value:' . (int) $this->rule->target_value . '';
        $output .= '}';
        return $output;
    }
}
