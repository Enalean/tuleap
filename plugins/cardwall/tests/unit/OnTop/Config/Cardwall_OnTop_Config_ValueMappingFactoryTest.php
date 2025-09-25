<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\OnTop\Config;

use Cardwall_Column;
use Cardwall_OnTop_ColumnMappingFieldValueDao;
use Cardwall_OnTop_Config_ValueMappingFactory;
use TestHelper;
use Tracker_FormElementFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveSemanticStatusFieldStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_OnTop_Config_ValueMappingFactoryTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalLanguageMock;

    private Cardwall_OnTop_Config_ValueMappingFactory $factory;
    private TrackerField $field_123;
    private TrackerField $field_124;
    private Tracker $tracker;
    private Tracker $tracker_10;
    private Tracker $tracker_20;

    #[\Override]
    protected function setUp(): void
    {
        $element_factory  = $this->createMock(Tracker_FormElementFactory::class);
        $dao              = $this->createMock(Cardwall_OnTop_ColumnMappingFieldValueDao::class);
        $this->field_123  = ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(123)->build()
        )->build()->getField();
        $this->field_124  = ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(124)->build()
        )->withStaticValues([1001 => 'value_1001', 1002 => 'value_1002'])->build()->getField();
        $this->tracker_10 = TrackerTestBuilder::aTracker()->withId(10)->build();
        $status_field     = ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(125)->thatIsRequired()->inTracker($this->tracker_10)->build()
        )->withStaticValues([
            1000 => 'value_1000',
            1001 => 'Todo',
            1002 => 'On Going',
            1003 => 'Done',
        ])->build()->getField();

        $element_factory->method('getFieldById')
            ->willReturnCallback(fn(int $field_id) => match ($field_id) {
                123     => $this->field_123,
                124     => $this->field_124,
                125     => $status_field,
                default => self::fail("Should not have been called with $field_id"),
            });

        $group_id         = 234;
        $project          = ProjectTestBuilder::aProject()->withId($group_id)->build();
        $this->tracker    = TrackerTestBuilder::aTracker()->withId(3)->withProject($project)->build();
        $this->tracker_20 = TrackerTestBuilder::aTracker()->withId(20)->build();

        $dao->method('searchMappingFieldValues')->with($this->tracker->getId())->willReturn(TestHelper::arrayToDar(
            [
                'tracker_id' => 10,
                'field_id'   => 125,
                'value_id'   => 1000,
                'column_id'  => 1,
            ],
            [
                'tracker_id' => 20,
                'field_id'   => 124,
                'value_id'   => 1001,
                'column_id'  => 1,
            ],
            [
                'tracker_id' => 20,
                'field_id'   => 124,
                'value_id'   => 1002,
                'column_id'  => 2,
            ]
        ));

        $this->factory = new Cardwall_OnTop_Config_ValueMappingFactory(
            $element_factory,
            $dao,
            RetrieveSemanticStatusFieldStub::build()->withField($status_field)
        );
    }

    public function testItLoadsMappingsFromTheDatabase(): void
    {
        $mappings = $this->factory->getMappings($this->tracker, $this->tracker_20, $this->field_124);
        self::assertCount(2, $mappings);
        self::assertEquals(1002, $mappings[1002]->getValueId());
    }

    public function testItLoadStatusValues(): void
    {
        $columns = new ColumnCollection([
            new Cardwall_Column(1, 'Todo', 'white'),
            new Cardwall_Column(2, 'In Progress', 'white'),
            new Cardwall_Column(3, 'Done', 'white'),
        ]);

        $mappings = $this->factory->getStatusMappings($this->tracker_10, $columns);
        self::assertEquals(1, $mappings[1001]->getColumnId());
        self::assertFalse(isset($mappings[1002]));
        self::assertEquals(3, $mappings[1003]->getColumnId());
    }

    public function testItLoadsMappingsFromTheDatabase2(): void
    {
        $element_factory        = $this->createMock(Tracker_FormElementFactory::class);
        $dao                    = $this->createMock(Cardwall_OnTop_ColumnMappingFieldValueDao::class);
        $status_field_retriever = RetrieveSemanticStatusFieldStub::build();
        $factory                = new Cardwall_OnTop_Config_ValueMappingFactory($element_factory, $dao, $status_field_retriever);

        $field_124 = ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(124)->build()
        )->withStaticValues([])->build()->getField();
        $matcher   = $this->exactly(2);

        $element_factory->expects($matcher)->method('getFieldById')->willReturnCallback(function (...$parameters) use ($matcher, $field_124) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(125, $parameters[0]);
                return null;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(124, $parameters[0]);
                return $field_124;
            }
        });

        $group_id   = 234;
        $project    = ProjectTestBuilder::aProject()->withId($group_id)->build();
        $tracker    = TrackerTestBuilder::aTracker()->withId(3)->withProject($project)->build();
        $tracker_20 = TrackerTestBuilder::aTracker()->withId(20)->build();

        $dao->method('searchMappingFieldValues')->with($tracker->getId())->willReturn(TestHelper::arrayToDar([
            'tracker_id' => 10,
            'field_id'   => 125,
            'value_id'   => 1000,
            'column_id'  => 1,
        ], [
            'tracker_id' => 20,
            'field_id'   => 124,
            'value_id'   => 1001,
            'column_id'  => 1,
        ]));

        $mappings = $factory->getMappings($tracker, $tracker_20, $field_124);
        self::assertEquals([], $mappings);
    }
}
