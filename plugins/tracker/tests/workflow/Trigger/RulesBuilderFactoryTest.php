<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once __DIR__.'/../../bootstrap.php';

class Tracker_Workflow_Trigger_RulesBuilderFactoryTest extends TuleapTestCase {

    /**
     * @var Tracker
     */
    private $target_tracker;

    public function setUp() {
        parent::setUp();
        $this->target_tracker = aTracker()->withId(12)->withChildren(array())->build();
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->factory = new Tracker_Workflow_Trigger_RulesBuilderFactory($this->formelement_factory);
    }

    public function itHasAllTargetTrackerSelectBoxFields() {
        expect($this->formelement_factory)->getUsedStaticSbFields($this->target_tracker)->once();
        stub($this->formelement_factory)->getUsedStaticSbFields()->returnsEmptyDar();

        $this->factory->getForTracker($this->target_tracker);
    }

    public function itHasAllTriggeringFields() {
        $child_tracker = aTracker()->withId(200)->build();
        $this->target_tracker->setChildren(array($child_tracker));

        expect($this->formelement_factory)->getUsedStaticSbFields()->count(2);
        expect($this->formelement_factory)->getUsedStaticSbFields($child_tracker)->at(1);
        stub($this->formelement_factory)->getUsedStaticSbFields()->returnsEmptyDar();

        $this->factory->getForTracker($this->target_tracker);
    }
}

?>
