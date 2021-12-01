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
use Tuleap\Tracker\TrackerColor;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->semantic_status_factory = Mockery::mock(Tracker_Semantic_StatusFactory::class);

        $this->retriever = new StatusValueRetriever(
            $this->semantic_status_factory
        );
    }

    public function testItThrowsAnExceptionIfTrackerDoesNotHaveStatusSemanticDefined(): void
    {
        $tracker = $this->buildTracker();
        $user    = UserTestBuilder::anActiveUser()->build();

        $this->mockSemanticStatusNotDefined($tracker);

        $this->expectException(SemanticStatusNotDefinedException::class);

        $this->retriever->getFirstClosedValueUserCanRead(
            $tracker,
            $user
        );

        $this->expectException(SemanticStatusNotDefinedException::class);

        $this->retriever->getFirstOpenValueUserCanRead(
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

        $this->expectException(SemanticStatusNotDefinedException::class);

        $this->retriever->getFirstClosedValueUserCanRead(
            $tracker,
            $user
        );

        $this->expectException(SemanticStatusNotDefinedException::class);

        $this->retriever->getFirstOpenValueUserCanRead(
            $tracker,
            $user
        );
    }

    public function testItThrowsAnExceptionIfAllValuesAreOpen(): void
    {
        $tracker = $this->buildTracker();
        $user    = UserTestBuilder::anActiveUser()->build();

        $this->mockSemanticStatusDefinedWithAllValuesAsOpen(
            $tracker,
            $user
        );

        $this->expectException(SemanticStatusClosedValueNotFoundException::class);

        $this->retriever->getFirstClosedValueUserCanRead(
            $tracker,
            $user
        );
    }

    public function testItThrowsAnExceptionIfAllClosedValueAreHidden(): void
    {
        $tracker = $this->buildTracker();
        $user    = UserTestBuilder::anActiveUser()->build();

        $this->mockSemanticStatusDefinedWithClosedValueHidden(
            $tracker,
            $user
        );

        $this->expectException(SemanticStatusClosedValueNotFoundException::class);

        $this->retriever->getFirstClosedValueUserCanRead(
            $tracker,
            $user
        );
    }

    public function testItReturnsTheFirstClosedValueFound(): void
    {
        $tracker = $this->buildTracker();
        $user    = UserTestBuilder::anActiveUser()->build();

        $this->mockSemanticStatusDefinedWithClosedValue(
            $tracker,
            $user
        );

        $field_value = $this->retriever->getFirstClosedValueUserCanRead(
            $tracker,
            $user
        );

        assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $field_value);
        assertSame(45, $field_value->getId());
    }

    public function testItThrowsAnExceptionIfAllValuesAreClosed(): void
    {
        $tracker = $this->buildTracker();
        $user    = UserTestBuilder::anActiveUser()->build();

        $this->mockSemanticStatusDefinedWithAllValuesClosed(
            $tracker,
            $user
        );

        $this->expectException(SemanticStatusOpenValueNotFoundException::class);

        $this->retriever->getFirstOpenValueUserCanRead(
            $tracker,
            $user
        );
    }

    public function testItThrowsAnExceptionIfAllOpenValueAreHidden(): void
    {
        $tracker = $this->buildTracker();
        $user    = UserTestBuilder::anActiveUser()->build();

        $this->mockSemanticStatusDefinedWithOpenValueHidden(
            $tracker,
            $user
        );

        $this->expectException(SemanticStatusOpenValueNotFoundException::class);

        $this->retriever->getFirstOpenValueUserCanRead(
            $tracker,
            $user
        );
    }

    public function testItReturnsTheFirstOpenValueFound(): void
    {
        $tracker = $this->buildTracker();
        $user    = UserTestBuilder::anActiveUser()->build();

        $this->mockSemanticStatusDefinedWithOpenValue(
            $tracker,
            $user
        );

        $field_value = $this->retriever->getFirstOpenValueUserCanRead(
            $tracker,
            $user
        );

        assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $field_value);
        assertSame(44, $field_value->getId());
    }

    private function mockSemanticStatusNotDefined(Tracker $tracker): void
    {
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn(
                new Tracker_Semantic_Status(
                    $tracker,
                    null,
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

        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn(
                new Tracker_Semantic_Status(
                    $tracker,
                    $field,
                    []
                )
            );
    }

    private function mockSemanticStatusDefinedWithAllValuesAsOpen(Tracker $tracker, PFUser $user): void
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

        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn(
                new Tracker_Semantic_Status(
                    $tracker,
                    $field,
                    [44, 45]
                )
            );
    }

    private function mockSemanticStatusDefinedWithAllValuesClosed(Tracker $tracker, PFUser $user): void
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

        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn(
                new Tracker_Semantic_Status(
                    $tracker,
                    $field,
                    []
                )
            );
    }

    private function mockSemanticStatusDefinedWithClosedValueHidden(Tracker $tracker, PFUser $user): void
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
                    ->andReturnTrue()
                    ->getMock(),
            ]);

        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn(
                new Tracker_Semantic_Status(
                    $tracker,
                    $field,
                    [44]
                )
            );
    }

    private function mockSemanticStatusDefinedWithOpenValueHidden(Tracker $tracker, PFUser $user): void
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
                    ->andReturnTrue()
                    ->getMock(),
            ]);

        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn(
                new Tracker_Semantic_Status(
                    $tracker,
                    $field,
                    [45]
                )
            );
    }

    private function mockSemanticStatusDefinedWithClosedValue(Tracker $tracker, PFUser $user): void
    {
        $not_open_value = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class);
        $not_open_value->shouldReceive('isHidden')->andReturnFalse();
        $not_open_value->shouldReceive('getId')->andReturn(45);

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
                45 => $not_open_value,
            ]);

        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn(
                new Tracker_Semantic_Status(
                    $tracker,
                    $field,
                    [44]
                )
            );
    }

    private function mockSemanticStatusDefinedWithOpenValue(Tracker $tracker, PFUser $user): void
    {
        $open_value = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class);
        $open_value->shouldReceive('isHidden')->andReturnFalse();
        $open_value->shouldReceive('getId')->andReturn(44);

        $field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $field->shouldReceive('userCanRead')
            ->once()
            ->with($user)
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

        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn(
                new Tracker_Semantic_Status(
                    $tracker,
                    $field,
                    [44]
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
