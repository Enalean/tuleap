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

require_once dirname(__FILE__) .'/../../../../include/constants.php';
require_once TRACKER_BASE_DIR .'/workflow/Action/Rules/EditRules.class.php';

class Tracker_Workflow_Action_Rules_EditRules_processTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->rule_1 = new Tracker_Rule_Date();
        $this->rule_1->setId(123);
        $this->rule_2 = new Tracker_Rule_Date();
        $this->rule_2->setId(456);
        $this->date_factory = mock('Tracker_Rule_Date_Factory');
        $tracker = mock('Tracker');
        $element_factory = mock('Tracker_FormElementFactory');
        $this->layout = mock('Tracker_IDisplayTrackerLayout');
        $this->user = mock('User');
        stub($this->date_factory)->searchById(123)->returns($this->rule_1);
        stub($this->date_factory)->searchById(456)->returns($this->rule_1);
        $this->action = new Tracker_Workflow_Action_Rules_EditRules($tracker, $element_factory, $this->date_factory);
    }

    public function itDeleteARule() {
        $request = aRequest()->with('remove_rule', array('123'))->build();
        expect($this->date_factory)->delete($this->rule_1)->once();
        $this->action->process($this->layout, $request, $this->user);
    }
}
?>
