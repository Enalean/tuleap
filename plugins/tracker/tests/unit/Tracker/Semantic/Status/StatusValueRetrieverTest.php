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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tracker;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_BindValue;
use Tracker_Semantic_Status;
use Tracker_Semantic_StatusFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertSame;

class StatusValueRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var StatusValueRetriever
     */
    private $retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Semantic_StatusFactory
     */
    private $semantic_status_factory;
    private Mockery\LegacyMockInterface|Mockery\MockInterface|FirstPossibleValueInListRetriever $first_possible_value_retriever;
    private Artifact $artifact;
    private Tracker_Semantic_Status $semantic_status;
    private PFUser $user;
    private Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker = $this->buildTracker();
        $this->user    = UserTestBuilder::anActiveUser()->build();

        parent::setUp();
        $this->artifact = ArtifactTestBuilder::anArtifact(112)->inTracker($this->tracker)->build();

        $this->semantic_status_factory        = Mockery::mock(Tracker_Semantic_StatusFactory::class);
        $this->first_possible_value_retriever = Mockery::mock(FirstPossibleValueInListRetriever::class);

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

        $this->first_possible_value_retriever->shouldReceive("getFirstPossibleValue")
            ->withArgs([$this->artifact, $this->semantic_status->getField(), Mockery::any(), $this->user])
            ->andReturn(45);

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

        $this->first_possible_value_retriever->shouldReceive("getFirstPossibleValue")
            ->withArgs([$this->artifact, $this->semantic_status->getField(), Mockery::any(), $this->user])
            ->andThrow(NoPossibleValueException::class);

        $this->expectException(NoPossibleValueException::class);

        $this->retriever->getFirstClosedValueUserCanRead(
            $this->user,
            $this->artifact
        );
    }

    public function testItThrowExceptionIfNoValidOpenValueFound(): void
    {
        $this->mockSemanticStatusDefinedWithOpenValue();

        $this->first_possible_value_retriever->shouldReceive("getFirstPossibleValue")
            ->withArgs([$this->artifact, $this->semantic_status->getField(), Mockery::any(), $this->user])
            ->andThrow(NoPossibleValueException::class);

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

        $this->first_possible_value_retriever->shouldReceive("getFirstPossibleValue")
            ->withArgs([$this->artifact, $this->semantic_status->getField(), Mockery::any(), $this->user])
            ->andReturn(44);

        $field_value = $this->retriever->getFirstOpenValueUserCanRead(
            $this->user,
            $this->artifact
        );

        assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $field_value);
        assertSame(44, $field_value->getId());
    }

    private function mockSemanticStatusNotDefined(): void
    {
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn(
                new Tracker_Semantic_Status(
                    $this->tracker,
                    null,
                    []
                )
            );
    }

    private function mockSemanticStatusNotDefinedWithFieldNonReadable(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive('userCanRead')
            ->once()
            ->with($this->user)
            ->andReturnFalse();

        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn(
                new Tracker_Semantic_Status(
                    $this->tracker,
                    $field,
                    []
                )
            );
    }

    private function mockSemanticStatusDefinedWithAllValuesAsOpen(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive('userCanRead')
            ->once()
            ->with($this->user)
            ->andReturnTrue();
        $field->shouldReceive('getAllValues')
            ->once()
            ->andReturn([
                44 => Mockery::mock(Tracker_FormElement_Field_List_BindValue::class)
                    ->shouldReceive('isHidden')
                    ->andReturnFalse()
                    ->getMock(),
                45 => Mockery::mock(Tracker_FormElement_Field_List_BindValue::class)
                    ->shouldReceive('isHidden')
                    ->andReturnFalse()
                    ->getMock(),
            ]);

        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn(
                new Tracker_Semantic_Status(
                    $this->tracker,
                    $field,
                    [44, 45]
                )
            );
    }

    private function mockSemanticStatusDefinedWithAllValuesClosed(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive('userCanRead')->once()->with($this->user)->andReturnTrue();
        $field->shouldReceive('getAllValues')
            ->once()
            ->andReturn([
                44 => Mockery::mock(Tracker_FormElement_Field_List_BindValue::class)
                    ->shouldReceive('isHidden')
                    ->andReturnFalse()
                    ->getMock(),
                45 => Mockery::mock(Tracker_FormElement_Field_List_BindValue::class)
                    ->shouldReceive('isHidden')
                    ->andReturnFalse()
                    ->getMock(),
            ]);
        $this->semantic_status = new Tracker_Semantic_Status(
            $this->tracker,
            $field,
            []
        );
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn(
                $this->semantic_status
            );
    }

    private function mockSemanticStatusDefinedWithClosedValueHidden(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive('userCanRead')
            ->once()
            ->with($this->user)
            ->andReturnTrue();
        $field->shouldReceive('getAllValues')
            ->once()
            ->andReturn([
                44 => Mockery::mock(Tracker_FormElement_Field_List_BindValue::class)
                    ->shouldReceive('isHidden')
                    ->andReturnFalse()
                    ->getMock(),
                45 => Mockery::mock(Tracker_FormElement_Field_List_BindValue::class)
                    ->shouldReceive('isHidden')
                    ->andReturnTrue()
                    ->getMock(),
            ]);
        $this->semantic_status = new Tracker_Semantic_Status(
            $this->tracker,
            $field,
            [44]
        );
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn(
                $this->semantic_status
            );
    }

    private function mockSemanticStatusDefinedWithOpenValueHidden(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive('userCanRead')
            ->once()
            ->with($this->user)
            ->andReturnTrue();
        $field->shouldReceive('getAllValues')
            ->once()
            ->andReturn([
                44 => Mockery::mock(Tracker_FormElement_Field_List_BindValue::class)
                    ->shouldReceive('isHidden')
                    ->andReturnFalse()
                    ->getMock(),
                45 => Mockery::mock(Tracker_FormElement_Field_List_BindValue::class)
                    ->shouldReceive('isHidden')
                    ->andReturnTrue()
                    ->getMock(),
            ]);
        $this->semantic_status = new Tracker_Semantic_Status(
            $this->tracker,
            $field,
            [45]
        );
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn(
                $this->semantic_status
            );
    }

    private function mockSemanticStatusDefinedWithClosedValue(): void
    {
        $not_open_value = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class);
        $not_open_value->shouldReceive('isHidden')->andReturnFalse();
        $not_open_value->shouldReceive('getId')->andReturn(45);

        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive('userCanRead')
            ->once()
            ->with($this->user)
            ->andReturnTrue();
        $field->shouldReceive('getAllValues')
            ->once()
            ->andReturn([
                44 => Mockery::mock(Tracker_FormElement_Field_List_BindValue::class)
                    ->shouldReceive('isHidden')
                    ->andReturnFalse()
                    ->getMock(),
                45 => $not_open_value,
            ]);
        $this->semantic_status = new Tracker_Semantic_Status(
            $this->tracker,
            $field,
            [44]
        );
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn(
                $this->semantic_status
            );
    }

    private function mockSemanticStatusDefinedWithOpenValue(): void
    {
        $open_value = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class);
        $open_value->shouldReceive('isHidden')->andReturnFalse();
        $open_value->shouldReceive('getId')->andReturn(44);

        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive('userCanRead')
            ->once()
            ->with($this->user)
            ->andReturnTrue();
        $field->shouldReceive('getAllValues')
            ->once()
            ->andReturn([
                44 => $open_value,
                45 => Mockery::mock(Tracker_FormElement_Field_List_BindValue::class)
                    ->shouldReceive('isHidden')
                    ->andReturnFalse()
                    ->getMock(),
            ]);
        $this->semantic_status = new Tracker_Semantic_Status(
            $this->tracker,
            $field,
            [44]
        );
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn(
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
            TrackerColor::default(),
            null
        );
    }
}
