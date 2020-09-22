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
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\NoTitleFieldException;

final class TargetFieldsGathererTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_Semantic_TitleFactory
     */
    private $semantic_title_factory;
    /**
     * @var TargetFieldsGatherer
     */
    private $gatherer;

    protected function setUp(): void
    {
        $this->semantic_title_factory = M::mock(\Tracker_Semantic_TitleFactory::class);
        $this->gatherer               = new TargetFieldsGatherer($this->semantic_title_factory);
    }

    public function testItReturnsTargetFields(): void
    {
        $tracker        = $this->buildTestTracker(27);
        $title_semantic = M::mock(\Tracker_Semantic_Title::class);
        $title_semantic->shouldReceive('getFieldId')->andReturn(1001);
        $this->semantic_title_factory->shouldReceive('getByTracker')->andReturn($title_semantic);

        $fields = $this->gatherer->gather($tracker);

        $this->assertEquals(1001, $fields->getTitleFieldId());
    }

    public function testItThrowsWhenTrackerHasNoTitleSemanticField(): void
    {
        $tracker        = $this->buildTestTracker(27);
        $title_semantic = M::mock(\Tracker_Semantic_Title::class);
        $title_semantic->shouldReceive('getFieldId')->andReturn(0);
        $this->semantic_title_factory->shouldReceive('getByTracker')->andReturn($title_semantic);

        $this->expectException(NoTitleFieldException::class);
        $this->gatherer->gather($tracker);
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
