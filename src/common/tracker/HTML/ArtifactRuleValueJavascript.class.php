<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Originally written by Nicolas Terray, 2006
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

/**
* Javascript representation (JSON) of a rule
*
*/
class ArtifactRuleValueJavascript extends ArtifactRuleValueView
{

    /**
     *  ArtifactRuleHtml() - constructor
     *
     *  @param $artifact_rule object
     */
    public function __construct(&$rule)
    {
        parent::__construct($rule);
    }

    public function fetch()
    {
        $output  = '{id:' . (int) $this->rule->id . ', ';
        $output .= 'group_artifact_id:' . (int) $this->rule->group_artifact_id . ', ';
        $output .= 'source_field:' . (int) $this->rule->source_field . ', ';
        $output .= 'source_value:' . (int) $this->rule->source_value . ', ';
        $output .= 'target_field:' . (int) $this->rule->target_field . ', ';
        $output .= 'target_value:' . (int) $this->rule->target_value . '';
        $output .= '}';
        return $output;
    }
}
