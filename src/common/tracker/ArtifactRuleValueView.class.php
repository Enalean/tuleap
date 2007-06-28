<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 *
 * Originally written by Nicolas Terray, 2006
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
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
