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

namespace Tuleap\Taskboard\Column;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Cardwall\OnTop\Config\ColumnCollection;
use Tuleap\Cardwall\OnTop\Config\ColumnFactory;
use Tuleap\Cardwall\Test\Builders\ColumnTestBuilder;
use Tuleap\Option\Option;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\ArtifactMappedFieldValueRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldValuesRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\SearchMappedFieldStub;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\SearchMappedFieldValuesForColumnStub;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\VerifyMappingExistsStub;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValuesRetriever;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\FormElement\Field\ListFields\RetrieveUsedListFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CardColumnFinderTest extends TestCase
{
    private const ONGOING_COLUMN_ID       = 60;
    private const TODO_BIND_VALUE_ID      = 1896;
    private const ON_GOING_BIND_VALUE_ID  = 1555;
    private const IN_REVIEW_BIND_VALUE_ID = 1083;
    private const DONE_BIND_VALUE_ID      = 1758;
    private const CANCELLED_BIND_VALUE_ID = 3116;
    private const MAPPED_FIELD_ID         = 1311;
    private ColumnFactory&Stub $column_factory;
    private \PFUser $user;
    private \Tracker_FormElement_Field_Selectbox $mapped_list_field;
    private \Tracker_FormElement_Field_List_Bind_Static $list_bind;
    private \Cardwall_Column $todo_column;
    private \Cardwall_Column $ongoing_column;
    private \Cardwall_Column $done_column;

    #[\Override]
    protected function setUp(): void
    {
        $this->user              = UserTestBuilder::buildWithDefaults();
        $this->mapped_list_field = ListFieldBuilder::aListField(self::MAPPED_FIELD_ID)
            ->withReadPermission($this->user, true)
            ->build();
        $this->list_bind         = ListStaticBindBuilder::aStaticBind($this->mapped_list_field)
            ->withStaticValues([
                self::TODO_BIND_VALUE_ID      => 'To do',
                self::ON_GOING_BIND_VALUE_ID  => 'On Going',
                self::IN_REVIEW_BIND_VALUE_ID => 'In Review',
                self::DONE_BIND_VALUE_ID      => 'Done',
                self::CANCELLED_BIND_VALUE_ID => 'Cancelled',
            ])->build();

        $this->todo_column    = ColumnTestBuilder::aColumn()->withId(59)->build();
        $this->ongoing_column = ColumnTestBuilder::aColumn()->withId(self::ONGOING_COLUMN_ID)->build();
        $this->done_column    = ColumnTestBuilder::aColumn()->withId(61)->build();

        $this->column_factory = $this->createStub(ColumnFactory::class);
        $this->column_factory->method('getDashboardColumns')
            ->willReturn(new ColumnCollection([$this->todo_column, $this->ongoing_column, $this->done_column]));
    }

    /**
     * @param list<\Tracker_FormElement_Field_List_BindValue> $card_mapped_field_value
     * @return Option<\Cardwall_Column>
     */
    private function getColumn(array $card_mapped_field_value): Option
    {
        $release_tracker = TrackerTestBuilder::aTracker()->withId(8)->build();

        $last_changeset = ChangesetTestBuilder::aChangeset(860)->build();
        ChangesetValueListTestBuilder::aListOfValue(17649, $last_changeset, $this->mapped_list_field)
            ->withValues($card_mapped_field_value)
            ->build();

        $card_tracker      = TrackerTestBuilder::aTracker()->withId(7)->build();
        $card_artifact     = ArtifactTestBuilder::anArtifact(567)
            ->inTracker($card_tracker)
            ->withChangesets($last_changeset)
            ->build();
        $taskboard_tracker = new TaskboardTracker($release_tracker, $card_tracker);

        $status_field_retriever = $this->createStub(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);

        $finder = new CardColumnFinder(
            new ArtifactMappedFieldValueRetriever(
                new MappedFieldRetriever(
                    $status_field_retriever,
                    new FreestyleMappedFieldRetriever(
                        SearchMappedFieldStub::withMappedField($taskboard_tracker, self::MAPPED_FIELD_ID),
                        RetrieveUsedListFieldStub::withField($this->mapped_list_field)
                    )
                )
            ),
            $this->column_factory,
            new MappedValuesRetriever(
                new FreestyleMappedFieldValuesRetriever(
                    VerifyMappingExistsStub::withMapping(),
                    SearchMappedFieldValuesForColumnStub::withMappings(
                        [$taskboard_tracker, $this->todo_column, [self::TODO_BIND_VALUE_ID]],
                        [$taskboard_tracker, $this->ongoing_column, [self::ON_GOING_BIND_VALUE_ID, self::IN_REVIEW_BIND_VALUE_ID]],
                        [$taskboard_tracker, $this->done_column, [self::DONE_BIND_VALUE_ID]],
                    )
                ),
                $status_field_retriever
            )
        );
        return $finder->findColumnOfCard($release_tracker, $card_artifact, $this->user);
    }

    public function testItFindsTheColumnThatIsMappedToTheValueOfTheCard(): void
    {
        $card_mapped_field_value = [$this->list_bind->getValue(self::ON_GOING_BIND_VALUE_ID)];

        self::assertSame(self::ONGOING_COLUMN_ID, $this->getColumn($card_mapped_field_value)->unwrapOr(null)?->getId());
    }

    public function testWhenTheCardHasTwoValuesItFindsTheColumnMappedToTheFirstValue(): void
    {
        $card_mapped_field_value = [
            $this->list_bind->getValue(self::IN_REVIEW_BIND_VALUE_ID),
            $this->list_bind->getValue(self::DONE_BIND_VALUE_ID),
        ];

        self::assertSame(self::ONGOING_COLUMN_ID, $this->getColumn($card_mapped_field_value)->unwrapOr(null)?->getId());
    }

    public function testItReturnsNothingWhenTheCardHasNoValueForTheMappedField(): void
    {
        $card_mapped_field_value = [];

        self::assertTrue($this->getColumn($card_mapped_field_value)->isNothing());
    }

    public function testItReturnsNothingWhenNoColumnMatchesTheValueOfTheCard(): void
    {
        $card_mapped_field_value = [$this->list_bind->getValue(self::CANCELLED_BIND_VALUE_ID)];

        self::assertTrue($this->getColumn($card_mapped_field_value)->isNothing());
    }

    public function testItReturnsNothingWhenTaskboardHasNoColumn(): void
    {
        $this->column_factory = $this->createStub(ColumnFactory::class);
        $this->column_factory->method('getDashboardColumns')->willReturn(new ColumnCollection());

        $card_mapped_field_value = [$this->list_bind->getValue(self::ON_GOING_BIND_VALUE_ID)];

        self::assertTrue($this->getColumn($card_mapped_field_value)->isNothing());
    }
}
