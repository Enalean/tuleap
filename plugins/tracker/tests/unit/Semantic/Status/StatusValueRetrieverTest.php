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

namespace Tuleap\Tracker\Semantic\Status;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_List_BindValue;
use Tuleap\Color\ColorName;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertSame;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StatusValueRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private StatusValueRetriever $retriever;
    private TrackerSemanticStatusFactory&MockObject $semantic_status_factory;
    private FirstPossibleValueInListRetriever&MockObject $first_possible_value_retriever;
    private Artifact $artifact;
    private TrackerSemanticStatus $semantic_status;
    private PFUser $user;
    private Tracker $tracker;

    #[\Override]
    protected function setUp(): void
    {
        $this->tracker = $this->buildTracker();
        $this->user    = UserTestBuilder::anActiveUser()->build();

        parent::setUp();
        $this->artifact = ArtifactTestBuilder::anArtifact(112)->inTracker($this->tracker)->build();

        $this->semantic_status_factory        = $this->createMock(TrackerSemanticStatusFactory::class);
        $this->first_possible_value_retriever = $this->createMock(FirstPossibleValueInListRetriever::class);

        $this->retriever = new StatusValueRetriever(
            $this->semantic_status_factory,
            $this->first_possible_value_retriever
        );
    }

    public function testItThrowsAnExceptionIfTrackerDoesNotHaveStatusSemanticDefined(): void
    {
        $this->mockSemanticStatusNotDefined();

        $this->expectException(SemanticStatusNotDefinedException::class);

        $this->retriever->getFirstClosedValueUserCanRead(
            $this->user,
            $this->artifact
        );

        $this->expectException(SemanticStatusNotDefinedException::class);

        $this->retriever->getFirstOpenValueUserCanRead(
            $this->user,
            $this->artifact
        );
    }

    public function testItThrowsAnExceptionIfUserCannotReadStatusField(): void
    {
        $this->mockSemanticStatusNotDefinedWithFieldNonReadable();

        $this->expectException(SemanticStatusNotDefinedException::class);

        $this->retriever->getFirstClosedValueUserCanRead(
            $this->user,
            $this->artifact
        );

        $this->expectException(SemanticStatusNotDefinedException::class);

        $this->retriever->getFirstOpenValueUserCanRead(
            $this->user,
            $this->artifact
        );
    }

    public function testItThrowsAnExceptionIfAllValuesAreOpen(): void
    {
        $this->mockSemanticStatusDefinedWithAllValuesAsOpen();

        $this->expectException(SemanticStatusClosedValueNotFoundException::class);

        $this->retriever->getFirstClosedValueUserCanRead(
            $this->user,
            $this->artifact
        );
    }

    public function testItThrowsAnExceptionIfAllClosedValueAreHidden(): void
    {
        $this->mockSemanticStatusDefinedWithClosedValueHidden();

        $this->expectException(SemanticStatusClosedValueNotFoundException::class);

        $this->retriever->getFirstClosedValueUserCanRead(
            $this->user,
            $this->artifact
        );
    }

    public function testItReturnsTheFirstValidClosedValueFound(): void
    {
        $this->mockSemanticStatusDefinedWithClosedValue();

        $this->first_possible_value_retriever->method('getFirstPossibleValue')
            ->with($this->artifact, $this->semantic_status->getField(), self::anything(), $this->user)
            ->willReturn(45);

        $field_value = $this->retriever->getFirstClosedValueUserCanRead(
            $this->user,
            $this->artifact
        );

        assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $field_value);
        assertSame(45, $field_value->getId());
    }

    public function testItThrowExceptionIfNoValidClosedValueFound(): void
    {
        $this->mockSemanticStatusDefinedWithClosedValue();

        $this->first_possible_value_retriever->method('getFirstPossibleValue')
            ->with($this->artifact, $this->semantic_status->getField(), self::anything(), $this->user)
            ->willThrowException(new NoPossibleValueException());

        $this->expectException(NoPossibleValueException::class);

        $this->retriever->getFirstClosedValueUserCanRead(
            $this->user,
            $this->artifact
        );
    }

    public function testItThrowExceptionIfNoValidOpenValueFound(): void
    {
        $this->mockSemanticStatusDefinedWithOpenValue();

        $this->first_possible_value_retriever->method('getFirstPossibleValue')
            ->with($this->artifact, $this->semantic_status->getField(), self::anything(), $this->user)
            ->willThrowException(new NoPossibleValueException());

        $this->expectException(NoPossibleValueException::class);

        $this->retriever->getFirstOpenValueUserCanRead(
            $this->user,
            $this->artifact
        );
    }

    public function testItThrowsAnExceptionIfAllValuesAreClosed(): void
    {
        $this->mockSemanticStatusDefinedWithAllValuesClosed();

        $this->expectException(SemanticStatusOpenValueNotFoundException::class);

        $this->retriever->getFirstOpenValueUserCanRead(
            $this->user,
            $this->artifact
        );
    }

    public function testItThrowsAnExceptionIfAllOpenValueAreHidden(): void
    {
        $this->mockSemanticStatusDefinedWithOpenValueHidden();

        $this->expectException(SemanticStatusOpenValueNotFoundException::class);

        $this->retriever->getFirstOpenValueUserCanRead(
            $this->user,
            $this->artifact
        );
    }

    public function testItReturnsTheFirstOpenValueFound(): void
    {
        $this->mockSemanticStatusDefinedWithOpenValue();

        $this->first_possible_value_retriever->method('getFirstPossibleValue')
            ->with($this->artifact, $this->semantic_status->getField(), self::anything(), $this->user)
            ->willReturn(44);

        $field_value = $this->retriever->getFirstOpenValueUserCanRead(
            $this->user,
            $this->artifact
        );

        assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $field_value);
        assertSame(44, $field_value->getId());
    }

    private function mockSemanticStatusNotDefined(): void
    {
        $this->semantic_status_factory
            ->expects($this->once())
            ->method('getByTracker')
            ->with($this->tracker)
            ->willReturn(
                new TrackerSemanticStatus(
                    $this->tracker,
                    null,
                    []
                )
            );
    }

    private function mockSemanticStatusNotDefinedWithFieldNonReadable(): void
    {
        $field = SelectboxFieldBuilder::aSelectboxField(1001)->withReadPermission($this->user, false)->build();

        $this->semantic_status_factory
            ->expects($this->once())
            ->method('getByTracker')
            ->with($this->tracker)
            ->willReturn(
                new TrackerSemanticStatus(
                    $this->tracker,
                    $field,
                    []
                )
            );
    }

    private function mockSemanticStatusDefinedWithAllValuesAsOpen(): void
    {
        $field = ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(1001)
                    ->withReadPermission($this->user, true)
                    ->build()
        )->withStaticValues([
            44 => 'a',
            45 => 'b',
        ])->build()->getField();

        $this->semantic_status_factory
            ->expects($this->once())
            ->method('getByTracker')
            ->with($this->tracker)
            ->willReturn(
                new TrackerSemanticStatus(
                    $this->tracker,
                    $field,
                    [44, 45]
                )
            );
    }

    private function mockSemanticStatusDefinedWithAllValuesClosed(): void
    {
        $field = ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(1001)
                ->withReadPermission($this->user, true)
                ->build()
        )->withStaticValues([
            44 => 'a',
            45 => 'b',
        ])->build()->getField();

        $this->semantic_status = new TrackerSemanticStatus(
            $this->tracker,
            $field,
            []
        );
        $this->semantic_status_factory
            ->expects($this->once())
            ->method('getByTracker')
            ->with($this->tracker)
            ->willReturn(
                $this->semantic_status
            );
    }

    private function mockSemanticStatusDefinedWithClosedValueHidden(): void
    {
        $field = ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(1001)
                ->withReadPermission($this->user, true)
                ->build()
        )->withStaticValues(
            [
                44 => 'a',
                45 => 'b',
            ],
            [
                45 => true,
            ]
        )->build()->getField();

        $this->semantic_status = new TrackerSemanticStatus(
            $this->tracker,
            $field,
            [44]
        );
        $this->semantic_status_factory
            ->expects($this->once())
            ->method('getByTracker')
            ->with($this->tracker)
            ->willReturn(
                $this->semantic_status
            );
    }

    private function mockSemanticStatusDefinedWithOpenValueHidden(): void
    {
        $field = ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(1001)
                ->withReadPermission($this->user, true)
                ->build()
        )->withStaticValues(
            [
                44 => 'a',
                45 => 'b',
            ],
            [
                45 => true,
            ]
        )->build()->getField();

        $this->semantic_status = new TrackerSemanticStatus(
            $this->tracker,
            $field,
            [45]
        );
        $this->semantic_status_factory
            ->expects($this->once())
            ->method('getByTracker')
            ->with($this->tracker)
            ->willReturn(
                $this->semantic_status
            );
    }

    private function mockSemanticStatusDefinedWithClosedValue(): void
    {
        $field = ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(1001)
                ->withReadPermission($this->user, true)
                ->build()
        )->withStaticValues(
            [
                44 => 'a',
                45 => 'b',
            ]
        )->build()->getField();

        $this->semantic_status = new TrackerSemanticStatus(
            $this->tracker,
            $field,
            [44]
        );
        $this->semantic_status_factory
            ->expects($this->once())
            ->method('getByTracker')
            ->with($this->tracker)
            ->willReturn(
                $this->semantic_status
            );
    }

    private function mockSemanticStatusDefinedWithOpenValue(): void
    {
        $field = ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(1001)
                ->withReadPermission($this->user, true)
                ->build()
        )->withStaticValues(
            [
                44 => 'a',
                45 => 'b',
            ]
        )->build()->getField();

        $this->semantic_status = new TrackerSemanticStatus(
            $this->tracker,
            $field,
            [44]
        );
        $this->semantic_status_factory
            ->expects($this->once())
            ->method('getByTracker')
            ->with($this->tracker)
            ->willReturn(
                $this->semantic_status
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
