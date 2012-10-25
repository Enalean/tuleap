<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once(dirname(__FILE__).'/../include/constants.php');
require_once('Tracker_SemanticTestAbstract.php');
require_once(dirname(__FILE__).'/../include/Tracker/Tracker.class.php');
Mock::generate('Tracker');
require_once(dirname(__FILE__).'/../include/Tracker/Tooltip/Tracker_Tooltip.class.php');

class Tracker_TooltipTest extends Tracker_SemanticTestAbstract {
            
    public function itReturnsTheSemanticInSOAPFormat() {
        $soap_result = $this->tracker_semantic->exportToSOAP();
        $this->assertNull($soap_result);
    }

    public function itReturnsAnEmptySOAPArray() {
        $soap_result = $this->not_defined_tracker_semantic->exportToSOAP();
        $this->assertNull($soap_result);
    }   
    
    public function newField() {
        return;
    }
    
    public function newTrackerSemantic($tracker, $field = null) {
        return new Tracker_Tooltip($tracker);
    }
}
?>
