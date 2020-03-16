<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

require_once __DIR__ . '/../../bootstrap.php';

class Tracker_Workflow_Trigger_RulesBuilderFactoryTest extends TuleapTestCase
{

    /**
     * @var Tracker
     */
    private $target_tracker;

    /**
     * @var Tracker_Workflow_Trigger_RulesBuilderFactory
     */
    private $factory;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->target_tracker      = aTracker()->withId(12)->withChildren(array())->build();
        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $this->factory = new Tracker_Workflow_Trigger_RulesBuilderFactory($this->formelement_factory);
    }

    public function itHasAllTargetTrackerSelectBoxFields()
    {
        $this->formelement_factory->shouldReceive('getUsedStaticSbFields')
            ->with($this->target_tracker)
            ->once()
            ->andReturn(Mockery::spy(DataAccessResult::class));

        $this->factory->getForTracker($this->target_tracker);
    }

    public function itHasAllTriggeringFields()
    {
        $child_tracker = aTracker()->withId(200)->build();
        $this->target_tracker->setChildren(array($child_tracker));

        $this->formelement_factory->shouldReceive('getUsedStaticSbFields')
            ->with($this->target_tracker)
            ->once()
            ->andReturn(Mockery::spy(DataAccessResult::class));

        $this->formelement_factory->shouldReceive('getUsedStaticSbFields')
            ->with($child_tracker)
            ->once()
            ->andReturn(Mockery::spy(DataAccessResult::class));

        $this->factory->getForTracker($this->target_tracker);
    }
}
