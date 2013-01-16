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
 * Manage values in changeset for reference fields
 */
class Tracker_Artifact_ChangesetValue_Reference extends Tracker_Artifact_ChangesetValue_String {
    
    function _extractAllMatches($html) {
        $count = preg_match_all('/(\w+) #([\w-_]+:)?([\w\/&]+)+/', $html, $matches, PREG_SET_ORDER);
        return $matches[0];
    }
    
    public function getKeyword() {
        $matches = $this->_extractAllMatches($this->getText());
        return $matches[1];
    }
    
    public function getItemGroupId() {
        $matches = $this->_extractAllMatches($this->getText());
        return $matches[2];
    }
    
    public function getItemId() {
        $matches = $this->_extractAllMatches($this->getText());
        return $matches[3];
    }
    
}
?>
