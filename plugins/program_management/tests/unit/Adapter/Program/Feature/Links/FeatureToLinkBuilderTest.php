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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Links;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeaturePlanChange;

final class FeatureToLinkBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildsFeatureToPlan(): void
    {
        $feature_to_links = [
            ['artifact_id' => "123"],
            ['artifact_id' => "456"]
        ];

        $dao     = \Mockery::mock(ArtifactsLinkedToParentDao::class);
        $builder = new FeatureToLinkBuilder($dao);

        $dao->shouldReceive('getArtifactsLinkedToId')->with("123", 1)->andReturn([['id' => 789]]);
        $dao->shouldReceive('getArtifactsLinkedToId')->with("456", 1)->andReturn([['id' => 910]]);

        self::assertEquals(new FeaturePlanChange([789, 910]), $builder->buildFeatureChange($feature_to_links, 1));
    }
}
