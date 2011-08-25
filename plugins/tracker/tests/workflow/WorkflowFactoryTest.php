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

require_once(dirname(__FILE__).'/../../include/workflow/WorkflowFactory.class.php');
require_once(dirname(__FILE__).'/../../include/Tracker/Tracker.class.php');
Mock::generate('Tracker');

require_once(dirname(__FILE__).'/../../include/Tracker/FormElement/Tracker_FormElement_Field_List.class.php');
Mock::generate('Tracker_FormElement_Field_List');

class WorkflowFactoryTest extends UnitTestCase {

     public function testImport() {
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/importWorkflow.xml');
        
        $tracker = new MockTracker();
        
        $mapping = array(
                    'F32'  => 111,
                    'F32-V0' => 801,
                    'F32-V1' => 802
                  );
        
        $workflow = WorkflowFactory::instance()->getInstanceFromXML($xml, $mapping, $tracker);
        $this->assertEqual($workflow->getIsUsed(), 1);
        $this->assertEqual($workflow->getFieldId(), 111);
        $this->assertEqual(count($workflow->getTransitions()), 3);
    }
    
}

?>
