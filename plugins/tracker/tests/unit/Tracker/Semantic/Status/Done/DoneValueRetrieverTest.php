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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tracker;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_BindValue;
use Tracker_Semantic_Status;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

class DoneValueRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DoneValueRetriever
     */
    private $retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SemanticDoneFactory
     */
    private $semantic_done_factory;
    private Tracker $tracker;
    private Artifact $artifact;
    private Tracker_Semantic_Status $semantic_status;
    private PFUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = UserTestBuilder::anActiveUser()->build();

        $this->tracker  = $this->buildTracker();
        $this->artifact = ArtifactTestBuilder::anArtifact(112)->inTracker($this->tracker)->build();

        $this->first_possible_value_retriever = Mockery::mock(FirstPossibleValueInListRetriever::class);
        $this->semantic_done_factory          = Mockery::mock(SemanticDoneFactory::class);

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

        $this->first_possible_value_retriever->shouldReceive("getFirstPossibleValue")->withArgs(
            [$this->artifact, $this->semantic_status->getField(), Mockery::any(), $this->user]
        )->andReturn(45);

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

        $this->first_possible_value_retriever->shouldReceive("getFirstPossibleValue")->withArgs(
            [$this->artifact, $this->semantic_status->getField(), Mockery::any(), $this->user]
        )->andThrow(NoPossibleValueException::class);

        $this->expectException(NoPossibleValueException::class);

        $this->retriever->getFirstDoneValueUserCanRead(
            $this->artifact,
            $this->user
        );
    }

    private function mockSemanticStatusNotDefined(): void
    {
        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn(
                new SemanticDone(
                    $this->tracker,
                    new Tracker_Semantic_Status(
                        $this->tracker,
                        null,
                        []
                    ),
                    Mockery::mock(SemanticDoneDao::class),
                    Mockery::mock(SemanticDoneValueChecker::class),
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

        $this->semantic_status = new Tracker_Semantic_Status(
            $this->tracker,
            $field,
            []
        );

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn(
                new SemanticDone(
                    $this->tracker,
                    new Tracker_Semantic_Status(
                        $this->tracker,
                        $field,
                        []
                    ),
                    Mockery::mock(SemanticDoneDao::class),
                    Mockery::mock(SemanticDoneValueChecker::class),
                    []
                )
            );
    }

    private function mockSemanticStatusDefinedWithAllValuesHidden(): void
    {
        $hidden_done_value = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class);
        $hidden_done_value->shouldReceive('isHidden')->andReturnTrue();
        $hidden_done_value->shouldReceive('getId')->andReturn(45);

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
                45 => $hidden_done_value,
            ]);

        $this->semantic_status = new Tracker_Semantic_Status(
            $this->tracker,
            $field,
            [45]
        );

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn(
                new SemanticDone(
                    $this->tracker,
                    new Tracker_Semantic_Status(
                        $this->tracker,
                        $field,
                        [45]
                    ),
                    Mockery::mock(SemanticDoneDao::class),
                    Mockery::mock(SemanticDoneValueChecker::class),
                    [
                        $hidden_done_value,
                    ]
                )
            );
    }

    private function mockDoneSemanticDefinedWithDoneValue(): void
    {
        $done_value = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class);
        $done_value->shouldReceive('isHidden')->andReturnFalse();
        $done_value->shouldReceive('getId')->andReturn(45);

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
                45 => $done_value,
            ]);

        $this->semantic_status = new Tracker_Semantic_Status(
            $this->tracker,
            $field,
            [45]
        );

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn(
                new SemanticDone(
                    $this->tracker,
                    $this->semantic_status,
                    Mockery::mock(SemanticDoneDao::class),
                    Mockery::mock(SemanticDoneValueChecker::class),
                    [
                        $done_value,
                    ]
                )
            );
    }

    private function mockDoneSemanticDefinedWithoutDoneValue(): void
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

        $this->semantic_status = new Tracker_Semantic_Status(
            $this->tracker,
            $field,
            [45]
        );

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->once()
            ->with($this->tracker)
            ->andReturn(
                new SemanticDone(
                    $this->tracker,
                    $this->semantic_status,
                    Mockery::mock(SemanticDoneDao::class),
                    Mockery::mock(SemanticDoneValueChecker::class),
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
            TrackerColor::default(),
            null
        );
    }
}
