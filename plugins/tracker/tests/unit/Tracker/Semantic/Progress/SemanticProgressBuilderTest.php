<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SemanticProgressBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SemanticProgressDao
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var SemanticProgressBuilder
     */
    private $progress_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker
     */
    private $tracker;

    protected function setUp(): void
    {
        $this->dao                  = \Mockery::mock(SemanticProgressDao::class);
        $this->form_element_factory = \Mockery::mock(\Tracker_FormElementFactory::class);
        $this->progress_builder     = new SemanticProgressBuilder(
            $this->dao,
            $this->form_element_factory
        );

        $this->tracker = \Mockery::mock(\Tracker::class, ['getId' => 113]);
    }

    public function testItBuildsAnEmptySemanticProgressWhenItHasNotBeenConfiguredYet(): void
    {
        $this->dao->shouldReceive('searchByTrackerId')->andReturn(null)->once();
        $semantic = $this->progress_builder->getSemantic(
            $this->tracker
        );

        $this->assertFalse($semantic->isDefined());
    }

    public function testItBuildsAnEmptySemanticProgressWhenTheSemanticIsNotEffortBased(): void
    {
        $this->dao->shouldReceive('searchByTrackerId')->andReturn(
            [
                'total_effort_field_id' => null,
                'remaining_effort_field_id' => null
            ]
        )->once();
        $semantic = $this->progress_builder->getSemantic(
            $this->tracker
        );

        $this->assertFalse($semantic->isDefined());
    }

    public function testItBuildsAnEffortBasedSemanticProgress(): void
    {
        $total_effort_field     = \Mockery::mock(\Tracker_FormElement_Field_Numeric::class, ['getId' => 1001]);
        $remaining_effort_field = \Mockery::mock(\Tracker_FormElement_Field_Numeric::class, ['getId' => 1002]);

        $this->dao->shouldReceive('searchByTrackerId')->andReturn(
            [
                'total_effort_field_id' => 1001,
                'remaining_effort_field_id' => 1002
            ]
        )->once();
        $this->form_element_factory->shouldReceive('getUsedFieldByIdAndType')
            ->with(
                $this->tracker,
                1001,
                ['int', 'float', 'computed']
            )
            ->andReturn($total_effort_field)
            ->once();

        $this->form_element_factory->shouldReceive('getUsedFieldByIdAndType')
            ->with(
                $this->tracker,
                1002,
                ['int', 'float', 'computed']
            )
            ->andReturn($remaining_effort_field)
            ->once();

        $semantic           = $this->progress_builder->getSemantic($this->tracker);
        $computation_method = $semantic->getComputationMethod();

        $this->assertTrue(
            ($computation_method instanceof MethodBasedOnEffort)
        );

        $this->assertEquals(
            1001,
            $computation_method->getTotalEffortFieldId()
        );

        $this->assertEquals(
            1002,
            $computation_method->getRemainingEffortFieldId()
        );
    }

    public function testItReturnsANotConfiguredSemanticWhenTotalEffortFieldCantBeFound(): void
    {
        $this->dao->shouldReceive('searchByTrackerId')->andReturn(
            [
                'total_effort_field_id' => 1001,
                'remaining_effort_field_id' => 1002
            ]
        )->once();
        $this->form_element_factory->shouldReceive('getUsedFieldByIdAndType')
            ->with(
                $this->tracker,
                1001,
                ['int', 'float', 'computed']
            )
            ->andReturn(null)
            ->once();

        $this->form_element_factory->shouldReceive('getUsedFieldByIdAndType')
            ->with(
                $this->tracker,
                1002,
                ['int', 'float', 'computed']
            )
            ->andReturn(\Mockery::mock(\Tracker_FormElement_Field_Numeric::class))
            ->once();

        $semantic = $this->progress_builder->getSemantic($this->tracker);

        $this->assertFalse($semantic->isDefined());
        $this->assertTrue($semantic->getComputationMethod() instanceof MethodNotConfigured);
    }

    public function testReturnsANotConfiguredSemanticWhenRemainingEffortFieldCantBeFound(): void
    {
        $this->dao->shouldReceive('searchByTrackerId')->andReturn(
            [
                'total_effort_field_id' => 1001,
                'remaining_effort_field_id' => 1002
            ]
        )->once();
        $this->form_element_factory->shouldReceive('getUsedFieldByIdAndType')
            ->with(
                $this->tracker,
                1001,
                ['int', 'float', 'computed']
            )
            ->andReturn(\Mockery::mock(\Tracker_FormElement_Field_Numeric::class))
            ->once();

        $this->form_element_factory->shouldReceive('getUsedFieldByIdAndType')
            ->with(
                $this->tracker,
                1002,
                ['int', 'float', 'computed']
            )
            ->andReturn(null)
            ->once();

        $semantic = $this->progress_builder->getSemantic($this->tracker);

        $this->assertFalse($semantic->isDefined());
        $this->assertTrue($semantic->getComputationMethod() instanceof MethodNotConfigured);
    }

    public function testReturnsANotConfiguredSemanticIfAFieldIsNotNumeric(): void
    {
        $this->dao->shouldReceive('searchByTrackerId')->andReturn(
            [
                'total_effort_field_id' => 1001,
                'remaining_effort_field_id' => 1002
            ]
        )->once();
        $this->form_element_factory->shouldReceive('getUsedFieldByIdAndType')
            ->with(
                $this->tracker,
                1001,
                ['int', 'float', 'computed']
            )
            ->andReturn(\Mockery::mock(\Tracker_FormElement_Field_Numeric::class))
            ->once();

        $this->form_element_factory->shouldReceive('getUsedFieldByIdAndType')
            ->with(
                $this->tracker,
                1002,
                ['int', 'float', 'computed']
            )
            ->andReturn(\Mockery::mock(\Tracker_FormElement_Field_Date::class))
            ->once();

        $semantic = $this->progress_builder->getSemantic($this->tracker);

        $this->assertFalse($semantic->isDefined());
        $this->assertTrue($semantic->getComputationMethod() instanceof MethodNotConfigured);
    }
}
