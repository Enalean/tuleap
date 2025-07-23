<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle;

use Tuleap\Cardwall\Test\Builders\ColumnTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FreestyleMappingDaoTest extends \Tuleap\Test\PHPUnit\TestIntegrationTestCase
{
    private const TODO_COLUMN_ID                 = 46;
    private const ONGOING_COLUMN_ID              = 47;
    private const RELEASE_TRACKER_ID             = 51;
    private const USER_STORIES_TRACKER_ID        = 628;
    private const USER_STORIES_STATUS_FIELD_ID   = 581;
    private const USER_STORIES_STATUS_TODO_ID    = 1689;
    private const USER_STORIES_STATUS_ONGOING_ID = 1690;
    private const USER_STORIES_STATUS_WAITING_ID = 1691;
    private const TASKS_TRACKER_ID               = 629;
    private const TASKS_STATUS_FIELD_ID          = 623;
    private const TASKS_STATUS_TODO_ID           = 1955;
    private const TASKS_STATUS_ONGOING_ID        = 1956;

    private FreestyleMappingDao $dao;
    private \Tuleap\Tracker\Tracker $release;
    private \Cardwall_Column $todo_column;
    private \Cardwall_Column $ongoing_column;

    #[\Override]
    protected function setUp(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->insertMany('plugin_cardwall_on_top_column_mapping_field', [
            [
                'cardwall_tracker_id' => self::RELEASE_TRACKER_ID,
                'tracker_id'          => self::USER_STORIES_TRACKER_ID,
                'field_id'            => self::USER_STORIES_STATUS_FIELD_ID,
            ],
            [
                'cardwall_tracker_id' => self::RELEASE_TRACKER_ID,
                'tracker_id'          => self::TASKS_TRACKER_ID,
                'field_id'            => self::TASKS_STATUS_FIELD_ID,
            ],
        ]);
        $db->insertMany('plugin_cardwall_on_top_column_mapping_field_value', [
            [
                'cardwall_tracker_id' => self::RELEASE_TRACKER_ID,
                'tracker_id'          => self::USER_STORIES_TRACKER_ID,
                'field_id'            => self::USER_STORIES_STATUS_FIELD_ID,
                'value_id'            => self::USER_STORIES_STATUS_TODO_ID,
                'column_id'           => self::TODO_COLUMN_ID,
            ],
            [
                'cardwall_tracker_id' => self::RELEASE_TRACKER_ID,
                'tracker_id'          => self::USER_STORIES_TRACKER_ID,
                'field_id'            => self::USER_STORIES_STATUS_FIELD_ID,
                'value_id'            => self::USER_STORIES_STATUS_ONGOING_ID,
                'column_id'           => self::ONGOING_COLUMN_ID,
            ],
            [
                'cardwall_tracker_id' => self::RELEASE_TRACKER_ID,
                'tracker_id'          => self::USER_STORIES_TRACKER_ID,
                'field_id'            => self::USER_STORIES_STATUS_FIELD_ID,
                'value_id'            => self::USER_STORIES_STATUS_WAITING_ID,
                'column_id'           => self::ONGOING_COLUMN_ID,
            ],
            [
                'cardwall_tracker_id' => self::RELEASE_TRACKER_ID,
                'tracker_id'          => self::TASKS_TRACKER_ID,
                'field_id'            => self::TASKS_STATUS_FIELD_ID,
                'value_id'            => self::TASKS_STATUS_TODO_ID,
                'column_id'           => self::TODO_COLUMN_ID,
            ],
            [
                'cardwall_tracker_id' => self::RELEASE_TRACKER_ID,
                'tracker_id'          => self::TASKS_TRACKER_ID,
                'field_id'            => self::TASKS_STATUS_FIELD_ID,
                'value_id'            => self::TASKS_STATUS_ONGOING_ID,
                'column_id'           => self::ONGOING_COLUMN_ID,
            ],
        ]);

        $this->dao = new FreestyleMappingDao();

        $this->release        = TrackerTestBuilder::aTracker()->withId(self::RELEASE_TRACKER_ID)->build();
        $this->todo_column    = ColumnTestBuilder::aColumn()->withId(self::TODO_COLUMN_ID)->build();
        $this->ongoing_column = ColumnTestBuilder::aColumn()->withId(self::ONGOING_COLUMN_ID)->build();
    }

    public function testItFindsMappedFieldValues(): void
    {
        $user_stories_tracker = new TaskboardTracker(
            $this->release,
            TrackerTestBuilder::aTracker()->withId(self::USER_STORIES_TRACKER_ID)->build()
        );

        self::assertTrue($this->dao->doesFreestyleMappingExist($user_stories_tracker));

        $mapped_field = $this->dao->searchMappedField($user_stories_tracker);
        self::assertSame(self::USER_STORIES_STATUS_FIELD_ID, $mapped_field->unwrapOr(null));

        $values_mapped_to_todo = $this->dao->searchMappedFieldValuesForColumn(
            $user_stories_tracker,
            $this->todo_column
        );
        self::assertCount(1, $values_mapped_to_todo);
        self::assertSame(self::USER_STORIES_STATUS_TODO_ID, $values_mapped_to_todo[0]);

        $values_mapped_to_ongoing = $this->dao->searchMappedFieldValuesForColumn(
            $user_stories_tracker,
            $this->ongoing_column
        );
        self::assertCount(2, $values_mapped_to_ongoing);
        self::assertSame(self::USER_STORIES_STATUS_ONGOING_ID, $values_mapped_to_ongoing[0]);
        self::assertSame(self::USER_STORIES_STATUS_WAITING_ID, $values_mapped_to_ongoing[1]);
    }

    public function testItFindsMappedFieldValuesForOtherTracker(): void
    {
        $tasks_tracker = new TaskboardTracker(
            $this->release,
            TrackerTestBuilder::aTracker()->withId(self::TASKS_TRACKER_ID)->build()
        );

        self::assertTrue($this->dao->doesFreestyleMappingExist($tasks_tracker));

        $mapped_field = $this->dao->searchMappedField($tasks_tracker);
        self::assertSame(self::TASKS_STATUS_FIELD_ID, $mapped_field->unwrapOr(null));

        $values_mapped_to_todo = $this->dao->searchMappedFieldValuesForColumn($tasks_tracker, $this->todo_column);
        self::assertCount(1, $values_mapped_to_todo);
        self::assertSame(self::TASKS_STATUS_TODO_ID, $values_mapped_to_todo[0]);

        $values_mapped_to_ongoing = $this->dao->searchMappedFieldValuesForColumn($tasks_tracker, $this->ongoing_column);
        self::assertCount(1, $values_mapped_to_ongoing);
        self::assertSame(self::TASKS_STATUS_ONGOING_ID, $values_mapped_to_ongoing[0]);
    }

    public function testItDoesNotFindTrackerWithNoMapping(): void
    {
        $tracker_without_mapping = new TaskboardTracker(
            $this->release,
            TrackerTestBuilder::aTracker()->withId(656)->build()
        );
        self::assertFalse($this->dao->doesFreestyleMappingExist($tracker_without_mapping));

        $mapped_field = $this->dao->searchMappedField($tracker_without_mapping);
        self::assertTrue($mapped_field->isNothing());

        self::assertEmpty($this->dao->searchMappedFieldValuesForColumn($tracker_without_mapping, $this->todo_column));
    }
}
