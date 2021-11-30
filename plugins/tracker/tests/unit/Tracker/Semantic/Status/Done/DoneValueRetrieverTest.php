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
use Tuleap\Tracker\TrackerColor;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->semantic_done_factory = Mockery::mock(SemanticDoneFactory::class);

        $this->retriever = new DoneValueRetriever(
            $this->semantic_done_factory
        );
    }

    public function testItThrowsAnExceptionIfTrackerDoesNotHaveStatusSemanticDefined(): void
    {
        $tracker = $this->buildTracker();
        $user    = UserTestBuilder::anActiveUser()->build();

        $this->mockSemanticStatusNotDefined($tracker);

        $this->expectException(SemanticDoneNotDefinedException::class);

        $this->retriever->getFirstDoneValueUserCanRead(
            $tracker,
            $user
        );
    }

    public function testItThrowsAnExceptionIfUserCannotReadStatusField(): void
    {
        $tracker = $this->buildTracker();
        $user    = UserTestBuilder::anActiveUser()->build();

        $this->mockSemanticStatusNotDefinedWithFieldNonReadable(
            $tracker,
            $user
        );

        $this->expectException(SemanticDoneNotDefinedException::class);

        $this->retriever->getFirstDoneValueUserCanRead(
            $tracker,
            $user
        );
    }

    public function testItThrowsAnExceptionIfAllDoneValueAreHidden(): void
    {
        $tracker = $this->buildTracker();
        $user    = UserTestBuilder::anActiveUser()->build();

        $this->mockSemanticStatusDefinedWithAllValuesHidden(
            $tracker,
            $user
        );

        $this->expectException(SemanticDoneValueNotFoundException::class);

        $this->retriever->getFirstDoneValueUserCanRead(
            $tracker,
            $user
        );
    }

    public function testItThrowsAnExceptionIfThreIsNoDoneValues(): void
    {
        $tracker = $this->buildTracker();
        $user    = UserTestBuilder::anActiveUser()->build();

        $this->mockDoneSemanticDefinedWithoutDoneValue(
            $tracker,
            $user
        );

        $this->expectException(SemanticDoneValueNotFoundException::class);

        $this->retriever->getFirstDoneValueUserCanRead(
            $tracker,
            $user
        );
    }

    public function testItReturnsTheFirstDoneValueFound(): void
    {
        $tracker = $this->buildTracker();
        $user    = UserTestBuilder::anActiveUser()->build();

        $this->mockDoneSemanticDefinedWithDoneValue(
            $tracker,
            $user
        );

        $field_value = $this->retriever->getFirstDoneValueUserCanRead(
            $tracker,
            $user
        );

        self::assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $field_value);
        self::assertSame(45, $field_value->getId());
    }

    private function mockSemanticStatusNotDefined(Tracker $tracker): void
    {
        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->once()
            ->with($tracker)
            ->andReturn(
                new SemanticDone(
                    $tracker,
                    new Tracker_Semantic_Status(
                        $tracker,
                        null,
                        []
                    ),
                    Mockery::mock(SemanticDoneDao::class),
                    Mockery::mock(SemanticDoneValueChecker::class),
                    []
                )
            );
    }

    private function mockSemanticStatusNotDefinedWithFieldNonReadable(Tracker $tracker, PFUser $user): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive('userCanRead')
            ->once()
            ->with($user)
            ->andReturnFalse();

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->once()
            ->with($tracker)
            ->andReturn(
                new SemanticDone(
                    $tracker,
                    new Tracker_Semantic_Status(
                        $tracker,
                        $field,
                        []
                    ),
                    Mockery::mock(SemanticDoneDao::class),
                    Mockery::mock(SemanticDoneValueChecker::class),
                    []
                )
            );
    }

    private function mockSemanticStatusDefinedWithAllValuesHidden(Tracker $tracker, PFUser $user): void
    {
        $hidden_done_value = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class);
        $hidden_done_value->shouldReceive('isHidden')->andReturnTrue();
        $hidden_done_value->shouldReceive('getId')->andReturn(45);

        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive('userCanRead')
            ->once()
            ->with($user)
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

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->once()
            ->with($tracker)
            ->andReturn(
                new SemanticDone(
                    $tracker,
                    new Tracker_Semantic_Status(
                        $tracker,
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

    private function mockDoneSemanticDefinedWithDoneValue(Tracker $tracker, PFUser $user): void
    {
        $done_value = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class);
        $done_value->shouldReceive('isHidden')->andReturnFalse();
        $done_value->shouldReceive('getId')->andReturn(45);

        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive('userCanRead')
            ->once()
            ->with($user)
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

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->once()
            ->with($tracker)
            ->andReturn(
                new SemanticDone(
                    $tracker,
                    new Tracker_Semantic_Status(
                        $tracker,
                        $field,
                        [45]
                    ),
                    Mockery::mock(SemanticDoneDao::class),
                    Mockery::mock(SemanticDoneValueChecker::class),
                    [
                        $done_value,
                    ]
                )
            );
    }

    private function mockDoneSemanticDefinedWithoutDoneValue(Tracker $tracker, PFUser $user): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive('userCanRead')
            ->once()
            ->with($user)
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

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->once()
            ->with($tracker)
            ->andReturn(
                new SemanticDone(
                    $tracker,
                    new Tracker_Semantic_Status(
                        $tracker,
                        $field,
                        [44]
                    ),
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
