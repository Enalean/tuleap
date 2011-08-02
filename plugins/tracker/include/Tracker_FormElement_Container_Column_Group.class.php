<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2011. All rights reserved
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

class Tracker_FormElement_Container_Column_Group {
    
    public function fetchArtifact($columns, Tracker_Artifact $artifact, $submitted_values = array()) {
        return $this->fetchGroup($columns, 'fetchArtifactInGroup', array($artifact, $submitted_values));
    }
    
    public function fetchSubmit($columns, $submitted_values = array()) {
        return $this->fetchGroup($columns, 'fetchSubmitInGroup', array($submitted_values));
    }
    
    public function fetchSubmitMasschange($columns, $submitted_values = array()) {
        return $this->fetchGroup($columns, 'fetchSubmitMasschangeInGroup', array($submitted_values));
    }
    
    public function fetchAdmin($columns, $tracker) {
        return $this->fetchGroup($columns, 'fetchAdminInGroup', array($tracker));
    }
    
    protected function fetchGroup($columns, $method, $params) {
        $html = '';
        if (is_array($columns) && $columns) {
            $cells = array();
            foreach ($columns as $c) {
                if ($content = call_user_func_array(array($c, $method), $params)) {
                    $cells[] = '<td>'. $content .'</td>';
                }
            }
            if ($cells) {
                $html .= '<table width="100%"><tbody><tr valign="top">';
                $html .= implode('', $cells);
                $html .= '</tr></tbody></table>';
            }
        }
        return $html;
    }
    
}
?>
