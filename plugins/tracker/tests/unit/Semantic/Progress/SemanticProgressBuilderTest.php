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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SemanticProgressBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SemanticProgressDao&MockObject $dao;
    /**
     * @var SemanticProgressBuilder
     */
    private $progress_builder;
    private Tracker $tracker;
    private MethodBuilder&MockObject $method_builder;

    protected function setUp(): void
    {
        $this->dao              = $this->createMock(SemanticProgressDao::class);
        $this->method_builder   = $this->createMock(MethodBuilder::class);
        $this->progress_builder = new SemanticProgressBuilder(
            $this->dao,
            $this->method_builder
        );

        $this->tracker = TrackerTestBuilder::aTracker()->build();
    }

    public function testItBuildsAnEmptySemanticProgressWhenItHasNotBeenConfiguredYet(): void
    {
        $this->dao->expects($this->once())->method('searchByTrackerId')->willReturn(null);
        $semantic = $this->progress_builder->getSemantic(
            $this->tracker
        );

        $this->assertFalse($semantic->isDefined());
    }

    public function testItBuildsAnEffortBasedSemanticProgress(): void
    {
        $total_effort_field     = IntFieldBuilder::anIntField(1001)->build();
        $remaining_effort_field = IntFieldBuilder::anIntField(1002)->build();

        $this->dao->expects($this->once())->method('searchByTrackerId')->willReturn(
            [
                'total_effort_field_id' => 1001,
                'remaining_effort_field_id' => 1002,
                'artifact_link_type' => null,
            ]
        );

        $this->method_builder->expects($this->once())->method('buildMethodBasedOnEffort')
            ->with(
                $this->tracker,
                1001,
                1002
            )
            ->willReturn(
                new MethodBasedOnEffort(
                    $this->dao,
                    $total_effort_field,
                    $remaining_effort_field
                )
            );

        $semantic           = $this->progress_builder->getSemantic($this->tracker);
        $computation_method = $semantic->getComputationMethod();

        $this->assertInstanceOf(
            MethodBasedOnEffort::class,
            $computation_method
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

    public function testItBuildsAChildCountBasedSemanticProgress(): void
    {
        $this->dao->expects($this->once())->method('searchByTrackerId')->willReturn(
            [
                'total_effort_field_id' => null,
                'remaining_effort_field_id' => null,
                'artifact_link_type' => 'covered_by',
            ]
        );

        $this->method_builder->expects($this->once())->method('buildMethodBasedOnChildCount')
            ->with(
                $this->tracker,
                'covered_by',
            )
            ->willReturn(
                new MethodBasedOnLinksCount(
                    $this->dao,
                    'covered_by'
                )
            );

        $semantic           = $this->progress_builder->getSemantic($this->tracker);
        $computation_method = $semantic->getComputationMethod();

        $this->assertInstanceOf(
            MethodBasedOnLinksCount::class,
            $computation_method
        );
    }

    /**
     * @testWith [null, 1002, "_fixed_in"]
     *           [1001, null, "_fixed_in"]
     *           [null, null, null]
     *           [null, 1002, null]
     *           [1001, null, null]
     */
    public function testItReturnsAnInvalidSemantic(
        ?int $total_effort_field_id,
        ?int $remaining_effort_field_id,
        ?string $artifact_link_type,
    ): void {
        $this->dao->expects($this->once())->method('searchByTrackerId')->willReturn(
            [
                'total_effort_field_id' => $total_effort_field_id,
                'remaining_effort_field_id' => $remaining_effort_field_id,
                'artifact_link_type' => $artifact_link_type,
            ]
        );

        $this->method_builder->expects($this->never())->method('buildMethodBasedOnEffort');

        $semantic = $this->progress_builder->getSemantic($this->tracker);

        $this->assertFalse($semantic->isDefined());
        $this->assertInstanceOf(InvalidMethod::class, $semantic->getComputationMethod());
    }
}
