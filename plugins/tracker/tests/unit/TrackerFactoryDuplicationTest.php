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

declare(strict_types=1);

namespace Tuleap\Tracker;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Project\MappingRegistry;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDuplicator;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerFactoryDuplicationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \TrackerFactory&MockObject $tracker_factory;
    private SemanticTimeframeDuplicator&MockObject $semantic_timeframe_duplicator;
    private \Tracker_HierarchyFactory&MockObject $hierarchy_factory;
    private \Tracker_Workflow_Trigger_RulesManager&MockObject $trigger_rules_manager;
    private \Tracker_FormElementFactory&MockObject $formelement_factory;

    protected function setUp(): void
    {
        $this->hierarchy_factory             = $this->createMock(\Tracker_HierarchyFactory::class);
        $this->trigger_rules_manager         = $this->createMock(\Tracker_Workflow_Trigger_RulesManager::class);
        $this->formelement_factory           = $this->createMock(\Tracker_FormElementFactory::class);
        $this->semantic_timeframe_duplicator = $this->createMock(SemanticTimeframeDuplicator::class);
        $this->tracker_factory               = $this->createPartialMock(\TrackerFactory::class, [
            'getHierarchyFactory',
            'getFormElementFactory',
            'getTriggerRulesManager',
            'getSemanticTimeframeDuplicator',
            'getTrackersByGroupId',
            'create',
        ]);
        $this->tracker_factory->method('getHierarchyFactory')->willReturn($this->hierarchy_factory);
        $this->tracker_factory->method('getFormElementFactory')->willReturn($this->formelement_factory);
        $this->tracker_factory->method('getTriggerRulesManager')->willReturn($this->trigger_rules_manager);
        $this->tracker_factory->method('getSemanticTimeframeDuplicator')->willReturn($this->semantic_timeframe_duplicator);

        $this->formelement_factory->method('fixOriginalFieldIdsAfterDuplication');
        $this->trigger_rules_manager->method('duplicate');
        $this->hierarchy_factory->method('duplicate');
    }

    public function testDuplicateDuplicatesAllTrackersWithHierarchy(): void
    {
        $t1 = TrackerTestBuilder::aTracker()->withId(1234)
            ->withName('Bugs')
            ->withDescription('Bug Tracker')
            ->withShortName('bug')
            ->build();

        $trackers = [$t1];
        $this->tracker_factory->method('getTrackersByGroupId')->with(100)->willReturn($trackers);

        $t_new = TrackerTestBuilder::aTracker()->withId(555)->build();

        $mapping_registry = new MappingRegistry([]);
        $this->tracker_factory
            ->expects(self::once())
            ->method('create')
            ->with(999, $mapping_registry, 1234, 'Bugs', 'Bug Tracker', 'bug', 'inca-silver', [])
            ->willReturn(['tracker' => $t_new, 'field_mapping' => [], 'report_mapping' => []]);

        $this->hierarchy_factory->expects(self::once())->method('duplicate');

        $this->semantic_timeframe_duplicator->expects(self::once())->method('duplicateSemanticTimeframeForAllTrackers');

        $this->tracker_factory->duplicate(
            UserTestBuilder::buildWithDefaults(),
            new DBTransactionExecutorPassthrough(),
            ProjectTestBuilder::aProject()->withId(100)->build(),
            ProjectTestBuilder::aProject()->withId(999)->build(),
            $mapping_registry
        );
    }

    public function testDuplicateDuplicatesSharedFields(): void
    {
        $t1 = TrackerTestBuilder::aTracker()->withId(123)->build();
        $t2 = TrackerTestBuilder::aTracker()->withId(567)->build();

        $mapping_registry = new MappingRegistry([]);

        $trackers = [$t1, $t2];
        $this->tracker_factory->method('getTrackersByGroupId')->with(100)->willReturn($trackers);

        $t_new1 = TrackerTestBuilder::aTracker()->withId(1234)->build();
        $t_new2 = TrackerTestBuilder::aTracker()->withId(5678)->build();

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
        $this->tracker_factory->method('create')
            ->willReturnCallback(static fn(
                int $to_project_id,
                MappingRegistry $mapping_registry,
                int $new_tracker_id,
                string $new_tracker_name,
                string $new_tracker_description,
                string $new_tracker_short_name,
                string $new_tracker_color_name,
                array $ugroup_mapping,
            ) => match ($new_tracker_id) {
                123 => ['tracker' => $t_new1, 'field_mapping' => $t_new1_field_mapping, 'report_mapping' => []],
                567 => ['tracker' => $t_new2, 'field_mapping' => $t_new2_field_mapping, 'report_mapping' => []],
            });

        $this->formelement_factory->expects(self::once())->method('fixOriginalFieldIdsAfterDuplication')->with($to_project_id, $from_project_id, $full_field_mapping);
        $this->semantic_timeframe_duplicator->expects(self::once())->method('duplicateSemanticTimeframeForAllTrackers');

        $this->tracker_factory->duplicate(
            UserTestBuilder::buildWithDefaults(),
            new DBTransactionExecutorPassthrough(),
            ProjectTestBuilder::aProject()->withId($from_project_id)->build(),
            ProjectTestBuilder::aProject()->withId($to_project_id)->build(),
            $mapping_registry
        );
    }

    public function testDuplicateIgnoresNonDuplicatableTrackers(): void
    {
        $t1 = $this->createStub(\Tracker::class);
        $t1->method('mustBeInstantiatedForNewProjects')->willReturn(false);
        $t1->method('getId')->willReturn(5678);
        $trackers = [$t1];
        $this->tracker_factory->method('getTrackersByGroupId')->with(100)->willReturn($trackers);

        $this->tracker_factory->expects(self::never())->method('create');

        $this->tracker_factory->duplicate(
            UserTestBuilder::buildWithDefaults(),
            new DBTransactionExecutorPassthrough(),
            ProjectTestBuilder::aProject()->withId(100)->build(),
            ProjectTestBuilder::aProject()->withId(999)->build(),
            new MappingRegistry([])
        );
    }

    public function testDuplicateDuplicatesAllTriggerRules(): void
    {
        $t1 = TrackerTestBuilder::aTracker()->withId(1234)
            ->withName('Bugs')
            ->withDescription('Bug Tracker')
            ->withShortName('bug')
            ->build();

        $mapping_registry = new MappingRegistry([]);

        $trackers = [$t1];
        $this->tracker_factory->method('getTrackersByGroupId')->with(100)->willReturn($trackers);

        $t_new = TrackerTestBuilder::aTracker()->withId(555)->build();

        $this->tracker_factory->expects(self::once())
            ->method('create')
            ->with(999, $mapping_registry, 1234, 'Bugs', 'Bug Tracker', 'bug', 'inca-silver', [])
            ->willReturn(['tracker' => $t_new, 'field_mapping' => [], 'report_mapping' => []]);

        $this->trigger_rules_manager->expects(self::once())->method('duplicate');

        $this->semantic_timeframe_duplicator->expects(self::once())->method('duplicateSemanticTimeframeForAllTrackers');

        $this->tracker_factory->duplicate(
            UserTestBuilder::buildWithDefaults(),
            new DBTransactionExecutorPassthrough(),
            ProjectTestBuilder::aProject()->withId(100)->build(),
            ProjectTestBuilder::aProject()->withId(999)->build(),
            $mapping_registry
        );
    }

    public function testDuplicateDuplicatesAllTrackersWithSemanticTimeframe(): void
    {
        $tracker_to_duplicate = TrackerTestBuilder::aTracker()->withId(1234)
            ->withName('User Stories')
            ->withDescription('User Stories')
            ->withShortName('user_stories')
            ->build();

        $trackers = [$tracker_to_duplicate];
        $this->tracker_factory->method('getTrackersByGroupId')->with(100)->willReturn($trackers);

        $new_tracker = TrackerTestBuilder::aTracker()->withId(555)->build();

        $mapping_registry = new MappingRegistry([]);
        $this->tracker_factory->expects(self::once())
            ->method('create')
            ->with(999, $mapping_registry, 1234, 'User Stories', 'User Stories', 'user_stories', 'inca-silver', [])
            ->willReturn(['tracker' => $new_tracker, 'field_mapping' => [], 'report_mapping' => []]);

        $this->hierarchy_factory->expects(self::once())->method('duplicate');

        $this->semantic_timeframe_duplicator->expects(self::once())
            ->method('duplicateSemanticTimeframeForAllTrackers')
            ->with([], [1234 => 555]);

        $this->tracker_factory->duplicate(
            UserTestBuilder::buildWithDefaults(),
            new DBTransactionExecutorPassthrough(),
            ProjectTestBuilder::aProject()->withId(100)->build(),
            ProjectTestBuilder::aProject()->withId(999)->build(),
            $mapping_registry
        );
    }
}
