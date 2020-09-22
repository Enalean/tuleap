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

final class MirrorMilestoneFieldsDataTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItReturnsFieldsDataAsArrayForArtifactCreator(): void
    {
        $project = \Project::buildForTest();
        $tracker = M::mock(\Tracker::class);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $field = M::mock(\Tracker_FormElement_Field::class);
        $field->shouldReceive('getTracker')->andReturn($tracker);
        $title_changeset_value = new \Tracker_Artifact_ChangesetValue_String(
            12,
            M::mock(\Tracker_Artifact_Changeset::class),
            $field,
            true,
            'Aggregator Release',
            'text'
        );
        $copied_values         = new CopiedValues($title_changeset_value, 123456789, 112);
        $target_fields         = new TargetFields(1001);

        $fields_data = MirrorMilestoneFieldsData::fromCopiedValuesAndTargetFields($copied_values, $target_fields);

        $this->assertEquals([1001 => 'Aggregator Release'], $fields_data->toFieldsDataArray());
    }
}
