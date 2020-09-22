<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\NoTitleFieldException;

final class CopiedValuesGathererTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CopiedValuesGatherer
     */
    private $gatherer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_Semantic_TitleFactory
     */
    private $semantic_title_factory;

    protected function setUp(): void
    {
        $this->semantic_title_factory = M::mock(\Tracker_Semantic_TitleFactory::class);
        $this->gatherer               = new CopiedValuesGatherer($this->semantic_title_factory);
    }

    public function testItReturnsCopiedValues(): void
    {
        $artifact = M::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(104);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 123456789, '');
        $tracker   = $this->buildTestTracker(89);

        $title_field       = $this->buildTestStringField(1002, 89);
        $title_field_value = new \Tracker_Artifact_ChangesetValue_String(
            10000,
            $changeset,
            $title_field,
            true,
            'My awesome title',
            'text'
        );
        $changeset->setFieldValue($title_field, $title_field_value);
        $title_semantic = M::mock(\Tracker_Semantic_Title::class);
        $title_semantic->shouldReceive('getField')->andReturn($title_field);
        $this->semantic_title_factory->shouldReceive('getByTracker')->andReturn($title_semantic);

        $values = $this->gatherer->gather($changeset, $tracker);

        $this->assertSame($title_field_value, $values->getTitleValue());
        $this->assertSame(123456789, $values->getSubmittedOn());
        $this->assertEquals(104, $values->getArtifactId());
    }

    public function testItThrowsWhenTrackerHasNoTitleSemanticField(): void
    {
        $artifact  = M::mock(\Tracker_Artifact::class);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $tracker   = $this->buildTestTracker(89);

        $title_semantic = M::mock(\Tracker_Semantic_Title::class);
        $title_semantic->shouldReceive('getField')->andReturnNull();
        $this->semantic_title_factory->shouldReceive('getByTracker')->andReturn($title_semantic);

        $this->expectException(NoTitleFieldException::class);
        $this->gatherer->gather($changeset, $tracker);
    }

    public function testItThrowsWhenChangesetHasNoValueForTitleField(): void
    {
        $artifact  = M::mock(\Tracker_Artifact::class);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $tracker   = $this->buildTestTracker(89);

        $title_field = $this->buildTestStringField(1002, 89);
        $changeset->setNoFieldValue($title_field);
        $title_semantic = M::mock(\Tracker_Semantic_Title::class);
        $title_semantic->shouldReceive('getField')->andReturn($title_field);
        $this->semantic_title_factory->shouldReceive('getByTracker')->andReturn($title_semantic);

        $this->expectException(NoTitleChangesetValueException::class);
        $this->gatherer->gather($changeset, $tracker);
    }

    public function testItThrowsWhenTitleChangesetValueCannotBeCastToString(): void
    {
        $artifact  = M::mock(\Tracker_Artifact::class);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 1, '');
        $tracker   = $this->buildTestTracker(89);

        $title_field = $this->buildTestStringField(1002, 89);
        $changeset->setFieldValue(
            $title_field,
            new \Tracker_Artifact_ChangesetValue_Text(
                10000,
                $changeset,
                $title_field,
                true,
                'My awesome title',
                'text'
            )
        );
        $title_semantic = M::mock(\Tracker_Semantic_Title::class);
        $title_semantic->shouldReceive('getField')->andReturn($title_field);
        $this->semantic_title_factory->shouldReceive('getByTracker')->andReturn($title_semantic);

        $this->expectException(UnsupportedTitleFieldException::class);
        $this->gatherer->gather($changeset, $tracker);
    }

    private function buildTestStringField(int $id, int $tracker_id): \Tracker_FormElement_Field_String
    {
        return new \Tracker_FormElement_Field_String(
            $id,
            $tracker_id,
            1001,
            'title',
            'Title',
            'Irrelevant',
            true,
            'P',
            true,
            '',
            1
        );
    }

    private function buildTestTracker(int $tracker_id): \Tracker
    {
        return new \Tracker(
            $tracker_id,
            null,
            'Irrelevant',
            'Irrelevant',
            'irrelevant',
            false,
            null,
            null,
            null,
            null,
            true,
            false,
            \Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            \Tuleap\Tracker\TrackerColor::default(),
            false
        );
    }
}
