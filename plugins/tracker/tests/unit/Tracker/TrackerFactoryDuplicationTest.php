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
use Tuleap\Project\MappingRegistry;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDuplicator;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class TrackerFactoryDuplicationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    private SemanticTimeframeDao $semantic_timeframe_dao;
    private SemanticTimeframeDuplicator $semantic_timeframe_duplicator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tracker_factory               = \Mockery::mock(\TrackerFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->hierarchy_factory             = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $this->trigger_rules_manager         = \Mockery::spy(\Tracker_Workflow_Trigger_RulesManager::class);
        $this->formelement_factory           = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->semantic_timeframe_dao        = Mockery::mock(SemanticTimeframeDao::class);
        $this->semantic_timeframe_duplicator = Mockery::mock(SemanticTimeframeDuplicator::class);


        $this->tracker_factory->shouldReceive('getHierarchyFactory')->andReturns($this->hierarchy_factory);
        $this->tracker_factory->shouldReceive('getFormElementFactory')->andReturns($this->formelement_factory);
        $this->tracker_factory->shouldReceive('getTriggerRulesManager')->andReturns($this->trigger_rules_manager);
        $this->tracker_factory->shouldReceive('getSemanticTimeframeDuplicator')->andReturns($this->semantic_timeframe_duplicator);
    }

    public function testDuplicateDuplicatesAllTrackersWithHierarchy(): void
    {
        $t1 = $this->givenADuplicatableTracker(1234);
        $t1->shouldReceive('getName')->andReturns('Bugs');
        $t1->shouldReceive('getDescription')->andReturns('Bug Tracker');
        $t1->shouldReceive('getItemname')->andReturns('bug');

        $trackers = [$t1];
        $this->tracker_factory->shouldReceive('getTrackersByGroupId')->with(100)->andReturns($trackers);

        $t_new = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns(555)->getMock();

        $mapping_registry = new MappingRegistry([]);
        $this->tracker_factory
            ->shouldReceive('create')
            ->with(999, $mapping_registry, 1234, 'Bugs', 'Bug Tracker', 'bug', 'inca-silver', [])
            ->once()
            ->andReturns(['tracker' => $t_new, 'field_mapping' => [], 'report_mapping' => []]);

        $this->hierarchy_factory->shouldReceive('duplicate')->once();

        $this->semantic_timeframe_duplicator->shouldReceive('duplicateSemanticTimeframeForAllTrackers')->once();

        $this->tracker_factory->duplicate(100, 999, $mapping_registry);
    }

    public function testDuplicateDuplicatesSharedFields(): void
    {
        $t1 = $this->givenADuplicatableTracker(123);
        $t2 = $this->givenADuplicatableTracker(567);

        $mapping_registry = new MappingRegistry([]);

        $trackers = [$t1, $t2];
        $this->tracker_factory->shouldReceive('getTrackersByGroupId')->with(100)->andReturns($trackers);

        $t_new1 = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns(1234)->getMock();
        $t_new2 = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns(5678)->getMock();

        $t_new1_field_mapping = [
            ['from' => '11', 'to' => '111'],
            ['from' => '22', 'to' => '222'],
        ];
        $t_new2_field_mapping = [
            ['from' => '33', 'to' => '333'],
            ['from' => '44', 'to' => '444'],
        ];
        $full_field_mapping   = array_merge($t_new1_field_mapping, $t_new2_field_mapping);
        $to_project_id        = 999;
        $from_project_id      = 100;
        $this->tracker_factory
            ->shouldReceive('create')
            ->with(
                $to_project_id,
                $mapping_registry,
                123,
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                []
            )
            ->andReturns(
                [
                    'tracker'        => $t_new1,
                    'field_mapping'  => $t_new1_field_mapping,
                    'report_mapping' => [],
                ]
            );
        $this->tracker_factory
            ->shouldReceive('create')
            ->with(
                $to_project_id,
                $mapping_registry,
                567,
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                Mockery::any(),
                []
            )
            ->andReturns(
                [
                    'tracker'        => $t_new2,
                    'field_mapping'  => $t_new2_field_mapping,
                    'report_mapping' => [],
                ]
            );

        $this->formelement_factory->shouldReceive('fixOriginalFieldIdsAfterDuplication')->with($to_project_id, $from_project_id, $full_field_mapping)->once();

        $this->semantic_timeframe_duplicator->shouldReceive('duplicateSemanticTimeframeForAllTrackers')->once();

        $this->tracker_factory->duplicate($from_project_id, $to_project_id, $mapping_registry);
    }

    public function testDuplicateIgnoresNonDuplicatableTrackers(): void
    {
        $t1 = \Mockery::spy(\Tracker::class);
        $t1->shouldReceive('mustBeInstantiatedForNewProjects')->andReturns(false);
        $t1->shouldReceive('getId')->andReturns(5678);
        $trackers = [$t1];
        $this->tracker_factory->shouldReceive('getTrackersByGroupId')->with(100)->andReturns($trackers);

        $this->tracker_factory->shouldReceive('create')->never();

        $this->tracker_factory->duplicate(100, 999, new MappingRegistry([]));
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

        $mapping_registry = new MappingRegistry([]);

        $trackers = [$t1];
        $this->tracker_factory->shouldReceive('getTrackersByGroupId')->with(100)->andReturns($trackers);

        $t_new = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns(555)->getMock();

        $this->tracker_factory
            ->shouldReceive('create')
            ->with(999, $mapping_registry, 1234, 'Bugs', 'Bug Tracker', 'bug', 'inca-silver', [])
            ->once()
            ->andReturns(['tracker' => $t_new, 'field_mapping' => [], 'report_mapping' => []]);

        $this->trigger_rules_manager->shouldReceive('duplicate')->once();

        $this->semantic_timeframe_duplicator->shouldReceive('duplicateSemanticTimeframeForAllTrackers')->once();

        $this->tracker_factory->duplicate(100, 999, $mapping_registry);
    }

    public function testDuplicateDuplicatesAllTrackersWithSemanticTimeframe(): void
    {
        $tracker_to_duplicate = $this->givenADuplicatableTracker(1234);
        $tracker_to_duplicate->shouldReceive('getName')->andReturns('User Stories');
        $tracker_to_duplicate->shouldReceive('getDescription')->andReturns('User Stories');
        $tracker_to_duplicate->shouldReceive('getItemname')->andReturns('user_stories');

        $trackers = [$tracker_to_duplicate];
        $this->tracker_factory->shouldReceive('getTrackersByGroupId')->with(100)->andReturns($trackers);

        $new_tracker = \Mockery::mock(\Tracker::class)->shouldReceive('getId')->andReturns(555)->getMock();

        $mapping_registry = new MappingRegistry([]);
        $this->tracker_factory
            ->shouldReceive('create')
            ->with(999, $mapping_registry, 1234, 'User Stories', 'User Stories', 'user_stories', 'inca-silver', [])
            ->once()
            ->andReturns(['tracker' => $new_tracker, 'field_mapping' => [], 'report_mapping' => []]);

        $this->hierarchy_factory->shouldReceive('duplicate')->once();

        $this->semantic_timeframe_duplicator->shouldReceive('duplicateSemanticTimeframeForAllTrackers')
            ->once()
            ->with([], [1234 => 555]);

        $this->tracker_factory->duplicate(100, 999, $mapping_registry);
    }
}
