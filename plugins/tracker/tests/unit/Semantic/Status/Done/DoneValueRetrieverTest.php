<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Status\Done;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_List_BindValue;
use Tuleap\Color\ColorName;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DoneValueRetrieverTest extends TestCase
{
    private DoneValueRetriever $retriever;
    private SemanticDoneFactory&MockObject $semantic_done_factory;
    private Tracker $tracker;
    private Artifact $artifact;
    private TrackerSemanticStatus $semantic_status;
    private PFUser $user;
    private FirstPossibleValueInListRetriever&MockObject $first_possible_value_retriever;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = UserTestBuilder::anActiveUser()->build();

        $this->tracker  = $this->buildTracker();
        $this->artifact = ArtifactTestBuilder::anArtifact(112)->inTracker($this->tracker)->build();

        $this->first_possible_value_retriever = $this->createMock(FirstPossibleValueInListRetriever::class);
        $this->semantic_done_factory          = $this->createMock(SemanticDoneFactory::class);

        $this->retriever = new DoneValueRetriever(
            $this->semantic_done_factory,
            $this->first_possible_value_retriever
        );
    }

    public function testItThrowsAnExceptionIfTrackerDoesNotHaveStatusSemanticDefined(): void
    {
        $this->mockSemanticStatusNotDefined();

        $this->expectException(SemanticDoneNotDefinedException::class);

        $this->retriever->getFirstDoneValueUserCanRead(
            $this->artifact,
            $this->user
        );
    }

    public function testItThrowsAnExceptionIfUserCannotReadStatusField(): void
    {
        $this->mockSemanticStatusNotDefinedWithFieldNonReadable();

        $this->expectException(SemanticDoneNotDefinedException::class);

        $this->retriever->getFirstDoneValueUserCanRead(
            $this->artifact,
            $this->user
        );
    }

    public function testItThrowsAnExceptionIfAllDoneValueAreHidden(): void
    {
        $this->mockSemanticStatusDefinedWithAllValuesHidden();

        $this->expectException(SemanticDoneValueNotFoundException::class);

        $this->retriever->getFirstDoneValueUserCanRead(
            $this->artifact,
            $this->user
        );
    }

    public function testItThrowsAnExceptionIfThreIsNoDoneValues(): void
    {
        $this->mockDoneSemanticDefinedWithoutDoneValue();

        $this->expectException(SemanticDoneValueNotFoundException::class);

        $this->retriever->getFirstDoneValueUserCanRead(
            $this->artifact,
            $this->user
        );
    }

    public function testItReturnsTheFirstDoneValueFound(): void
    {
        $this->mockDoneSemanticDefinedWithDoneValue();

        $this->first_possible_value_retriever->method('getFirstPossibleValue')->with(
            $this->artifact,
            $this->semantic_status->getField(),
            self::anything(),
            $this->user
        )->willReturn(45);

        $field_value = $this->retriever->getFirstDoneValueUserCanRead(
            $this->artifact,
            $this->user
        );

        self::assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $field_value);
        self::assertSame(45, $field_value->getId());
    }

    public function testItThrowExceptionIfNoValidValueFound(): void
    {
        $this->mockDoneSemanticDefinedWithDoneValue();

        $this->first_possible_value_retriever->method('getFirstPossibleValue')->with(
            $this->artifact,
            $this->semantic_status->getField(),
            self::anything(),
            $this->user
        )->willThrowException(new NoPossibleValueException());

        $this->expectException(NoPossibleValueException::class);

        $this->retriever->getFirstDoneValueUserCanRead(
            $this->artifact,
            $this->user
        );
    }

    private function mockSemanticStatusNotDefined(): void
    {
        $this->semantic_done_factory
            ->expects($this->once())
            ->method('getInstanceByTracker')
            ->with($this->tracker)
            ->willReturn(
                new SemanticDone(
                    $this->tracker,
                    new TrackerSemanticStatus(
                        $this->tracker,
                        null,
                        []
                    ),
                    $this->createMock(SemanticDoneDao::class),
                    $this->createMock(SemanticDoneValueChecker::class),
                    []
                )
            );
    }

    private function mockSemanticStatusNotDefinedWithFieldNonReadable(): void
    {
        $field = SelectboxFieldBuilder::aSelectboxField(1001)->withReadPermission($this->user, false)->build();

        $this->semantic_status = new TrackerSemanticStatus(
            $this->tracker,
            $field,
            []
        );

        $this->semantic_done_factory
            ->expects($this->once())
            ->method('getInstanceByTracker')
            ->with($this->tracker)
            ->willReturn(
                new SemanticDone(
                    $this->tracker,
                    new TrackerSemanticStatus(
                        $this->tracker,
                        $field,
                        []
                    ),
                    $this->createMock(SemanticDoneDao::class),
                    $this->createMock(SemanticDoneValueChecker::class),
                    []
                )
            );
    }

    private function mockSemanticStatusDefinedWithAllValuesHidden(): void
    {
        $field = $this->createMock(ListField::class);
        $field->expects($this->once())
            ->method('userCanRead')
            ->with($this->user)
            ->willReturn(true);

        $value1 = $this->createMock(Tracker_FormElement_Field_List_BindValue::class);
        $value1->method('isHidden')->willReturn(false);

        $hidden_done_value = $this->createMock(Tracker_FormElement_Field_List_BindValue::class);
        $hidden_done_value->method('isHidden')->willReturn(true);
        $hidden_done_value->method('getId')->willReturn(45);

        $field
            ->expects($this->once())
            ->method('getAllValues')
            ->willReturn([
                44 => $value1,
                45 => $hidden_done_value,
            ]);

        $this->semantic_status = new TrackerSemanticStatus(
            $this->tracker,
            $field,
            [45]
        );

        $this->semantic_done_factory
            ->expects($this->once())
            ->method('getInstanceByTracker')
            ->with($this->tracker)
            ->willReturn(
                new SemanticDone(
                    $this->tracker,
                    new TrackerSemanticStatus(
                        $this->tracker,
                        $field,
                        [45]
                    ),
                    $this->createMock(SemanticDoneDao::class),
                    $this->createMock(SemanticDoneValueChecker::class),
                    [
                        $hidden_done_value,
                    ]
                )
            );
    }

    private function mockDoneSemanticDefinedWithDoneValue(): void
    {
        $field = $this->createMock(ListField::class);
        $field->expects($this->once())
            ->method('userCanRead')
            ->with($this->user)
            ->willReturn(true);

        $value1 = $this->createMock(Tracker_FormElement_Field_List_BindValue::class);
        $value1->method('isHidden')->willReturn(false);

        $done_value = $this->createMock(Tracker_FormElement_Field_List_BindValue::class);
        $done_value->method('isHidden')->willReturn(false);
        $done_value->method('getId')->willReturn(45);

        $field
            ->expects($this->once())
            ->method('getAllValues')
            ->willReturn([
                44 => $value1,
                45 => $done_value,
            ]);

        $this->semantic_status = new TrackerSemanticStatus(
            $this->tracker,
            $field,
            [45]
        );

        $this->semantic_done_factory
            ->expects($this->once())
            ->method('getInstanceByTracker')
            ->with($this->tracker)
            ->willReturn(
                new SemanticDone(
                    $this->tracker,
                    $this->semantic_status,
                    $this->createMock(SemanticDoneDao::class),
                    $this->createMock(SemanticDoneValueChecker::class),
                    [
                        $done_value,
                    ]
                )
            );
    }

    private function mockDoneSemanticDefinedWithoutDoneValue(): void
    {
        $field = $this->createMock(ListField::class);
        $field->expects($this->once())
            ->method('userCanRead')
            ->with($this->user)
            ->willReturn(true);

        $value1 = $this->createMock(Tracker_FormElement_Field_List_BindValue::class);
        $value1->method('isHidden')->willReturn(false);
        $value2 = $this->createMock(Tracker_FormElement_Field_List_BindValue::class);
        $value2->method('isHidden')->willReturn(false);

        $field
            ->expects($this->once())
            ->method('getAllValues')
            ->willReturn([
                44 => $value1,
                45 => $value2,
            ]);

        $this->semantic_status = new TrackerSemanticStatus(
            $this->tracker,
            $field,
            [45]
        );

        $this->semantic_done_factory
            ->expects($this->once())
            ->method('getInstanceByTracker')
            ->with($this->tracker)
            ->willReturn(
                new SemanticDone(
                    $this->tracker,
                    $this->semantic_status,
                    $this->createMock(SemanticDoneDao::class),
                    $this->createMock(SemanticDoneValueChecker::class),
                    []
                )
            );
    }

    private function buildTracker(): Tracker
    {
        return new Tracker(
            101,
            102,
            'Test 101',
            null,
            'test_101',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            ColorName::default(),
            null
        );
    }
}
