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

/**
* Basic view for a rule
*
*/
class ArtifactRuleValueView {

    var $rule;
    
	/**
	 *  ArtifactRuleValueView() - constructor
	 *
	 *  @param $artifact_rule object
	 */
	function ArtifactRuleValueView(&$rule) {
		$this->rule =& $rule;
	}
    
    function display() {
        echo $this->fetch();
    }
    
    /**
     * @return a representation of an artifact rule
     * #id@group_artifact_id source_field(source_value) => target_field(target_value_1, target_value_2)
     */
    function fetch() {
        $output  = '#'. $this->rule->id;
        $output .= '@'. $this->rule->group_artifact_id;
        $output .= ' '. $this->rule->source_field;
        $output .= '('. $this->rule->source_value .') =>';
        $output .= ' '. $this->rule->target_field;
        $output .= '('. $this->rule->target_value .')';
        return $output;
    }
}

?>
