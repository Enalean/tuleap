<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class TrackerFactoryDuplicationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tracker_factory   = \Mockery::mock(\TrackerFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->hierarchy_factory     = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $this->trigger_rules_manager = \Mockery::spy(\Tracker_Workflow_Trigger_RulesManager::class);
        $this->formelement_factory   = \Mockery::spy(\Tracker_FormElementFactory::class);

        $this->tracker_factory->shouldReceive('getHierarchyFactory')->andReturns($this->hierarchy_factory);
        $this->tracker_factory->shouldReceive('getFormElementFactory')->andReturns($this->formelement_factory);
        $this->tracker_factory->shouldReceive('getTriggerRulesManager')->andReturns($this->trigger_rules_manager);
    }

    public function testDuplicateDuplicatesAllTrackersWithHierarchy(): void
    {
        $t1 = $this->givenADuplicatableTracker(1234);
        $t1->shouldReceive('getName')->andReturns('Bugs');
        $t1->shouldReceive('getDescription')->andReturns('Bug Tracker');
        $t1->shouldReceive('getItemname')->andReturns('bug');

        $trackers = array($t1);
        $this->tracker_factory->shouldReceive('getTrackersByGroupId')->with(100)->andReturns($trackers);

        $t_new = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns(555)->getMock();

        $this->tracker_factory->shouldReceive('create')->with(999, 100, 1234, 'Bugs', 'Bug Tracker', 'bug', 'inca-silver', null)->once()->andReturns(array('tracker' => $t_new, 'field_mapping' => array(), 'report_mapping' => array()));

        $this->hierarchy_factory->shouldReceive('duplicate')->once();

        $this->tracker_factory->duplicate(100, 999, null);
    }

    public function testDuplicateDuplicatesSharedFields(): void
    {
        $t1 = $this->givenADuplicatableTracker(123);
        $t2 = $this->givenADuplicatableTracker(567);

        $trackers = [$t1, $t2];
        $this->tracker_factory->shouldReceive('getTrackersByGroupId')->with(100)->andReturns($trackers);

        $t_new1 = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns(1234)->getMock();
        $t_new2 = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns(5678)->getMock();

        $t_new1_field_mapping = [
            ['from' => '11', 'to' => '111'],
            ['from' => '22', 'to' => '222']
        ];
        $t_new2_field_mapping = [
            ['from' => '33', 'to' => '333'],
            ['from' => '44', 'to' => '444']
        ];
        $full_field_mapping = array_merge($t_new1_field_mapping, $t_new2_field_mapping);
        $to_project_id   = 999;
        $from_project_id = 100;
        $this->tracker_factory->shouldReceive('create')->with($to_project_id, $from_project_id, 123, Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), null)->andReturns([
            'tracker' => $t_new1,
            'field_mapping' => $t_new1_field_mapping,
            'report_mapping' => []
        ]);
        $this->tracker_factory->shouldReceive('create')->with($to_project_id, $from_project_id, 567, Mockery::any(), Mockery::any(), Mockery::any(), Mockery::any(), null)->andReturns([
            'tracker' => $t_new2,
            'field_mapping' => $t_new2_field_mapping,
            'report_mapping' => []
        ]);

        $this->formelement_factory->shouldReceive('fixOriginalFieldIdsAfterDuplication')->with($to_project_id, $from_project_id, $full_field_mapping)->once();
        $this->tracker_factory->duplicate($from_project_id, $to_project_id, []);
    }

    public function testDuplicateIgnoresNonDuplicatableTrackers(): void
    {
        $t1 = \Mockery::spy(\Tracker::class);
        $t1->shouldReceive('mustBeInstantiatedForNewProjects')->andReturns(false);
        $t1->shouldReceive('getId')->andReturns(5678);
        $trackers = array($t1);
        $this->tracker_factory->shouldReceive('getTrackersByGroupId')->with(100)->andReturns($trackers);

        $this->tracker_factory->shouldReceive('create')->never();

        $this->tracker_factory->duplicate(100, 999, null);
    }

    private function givenADuplicatableTracker($tracker_id): Tracker
    {
        $t1 = \Mockery::spy(\Tracker::class);
        $t1->shouldReceive('mustBeInstantiatedForNewProjects')->andReturns(true);
        $t1->shouldReceive('getId')->andReturns($tracker_id);
        $t1->shouldReceive('getColor')->andReturns(\Tuleap\Tracker\TrackerColor::default());
        return $t1;
    }

    public function testDuplicateDuplicatesAllTriggerRules(): void
    {
        $t1 = $this->givenADuplicatableTracker(1234);
        $t1->shouldReceive('getName')->andReturns('Bugs');
        $t1->shouldReceive('getDescription')->andReturns('Bug Tracker');
        $t1->shouldReceive('getItemname')->andReturns('bug');

        $trackers = array($t1);
        $this->tracker_factory->shouldReceive('getTrackersByGroupId')->with(100)->andReturns($trackers);

        $t_new = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns(555)->getMock();

        $this->tracker_factory->shouldReceive('create')->with(999, 100, 1234, 'Bugs', 'Bug Tracker', 'bug', 'inca-silver', null)->once()->andReturns(array('tracker' => $t_new, 'field_mapping' => array(), 'report_mapping' => array()));

        $this->trigger_rules_manager->shouldReceive('duplicate')->once();

        $this->tracker_factory->duplicate(100, 999, null);
    }
}
