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

require_once('bootstrap.php');
Mock::generate('Tracker_FormElement_Container_Column');
Mock::generate('Tracker_Artifact');


class Tracker_FormElement_Container_Column_GroupTest extends TuleapTestCase {
    
    public function test_fetchArtifact() {
        $a = new MockTracker_Artifact();
        $s = array();
        
        $c1 = new MockTracker_FormElement_Container_Column(); $c1->setReturnValue('fetchArtifactInGroup', 'C1', array($a, $s));
        $c2 = new MockTracker_FormElement_Container_Column(); $c2->setReturnValue('fetchArtifactInGroup', 'C2', array($a, $s));
        $c3 = new MockTracker_FormElement_Container_Column(); $c3->setReturnValue('fetchArtifactInGroup', 'C3', array($a, $s));
        $c4 = new MockTracker_FormElement_Container_Column(); $c4->setReturnValue('fetchArtifactInGroup', 'C4', array($a, $s));
        
        $empty = array();
        $one   = array($c1);
        $many  = array($c1, $c2, $c3, $c4);
        
        $g = new Tracker_FormElement_Container_Column_Group();
        $this->assertEqual($g->fetchArtifact($empty, $a, $s), '');
        $this->assertEqual($g->fetchArtifact($one, $a, $s), '<table width="100%"><tbody><tr valign="top">'.
            '<td>C1</td>'.
            '</tr></tbody></table>'
        );
        $this->assertEqual($g->fetchArtifact($many, $a, $s), '<table width="100%"><tbody><tr valign="top">'.
            '<td>C1</td>'.
            '<td>C2</td>'.
            '<td>C3</td>'.
            '<td>C4</td>'.
            '</tr></tbody></table>'
        );
    }
    
    public function test_fetchArtifact_with_empty_columns() {
        $a = new MockTracker_Artifact();
        $s = array();
        
        $c1 = new MockTracker_FormElement_Container_Column(); $c1->setReturnValue('fetchArtifactInGroup', '', array($a, $s));
        $c2 = new MockTracker_FormElement_Container_Column(); $c2->setReturnValue('fetchArtifactInGroup', 'C2', array($a, $s));
        $c3 = new MockTracker_FormElement_Container_Column(); $c3->setReturnValue('fetchArtifactInGroup', '', array($a, $s));
        $c4 = new MockTracker_FormElement_Container_Column(); $c4->setReturnValue('fetchArtifactInGroup', 'C4', array($a, $s));
        
        $one_c1   = array($c1);
        $one_c2   = array($c2);
        $many     = array($c1, $c2, $c3, $c4);
        
        $g = new Tracker_FormElement_Container_Column_Group();
        $this->assertEqual($g->fetchArtifact($one_c1, $a, $s), '');
        $this->assertEqual($g->fetchArtifact($one_c2, $a, $s), '<table width="100%"><tbody><tr valign="top">'.
            '<td>C2</td>'.
            '</tr></tbody></table>'
        );
        $this->assertEqual($g->fetchArtifact($many, $a, $s), '<table width="100%"><tbody><tr valign="top">'.
            '<td>C2</td>'.
            '<td>C4</td>'.
            '</tr></tbody></table>'
        );
    }
}
?>