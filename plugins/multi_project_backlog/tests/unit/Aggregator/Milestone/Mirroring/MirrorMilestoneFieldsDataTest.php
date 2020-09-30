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
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFields;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\TimeframeFields;
use Tuleap\MultiProjectBacklog\Aggregator\MirroredArtifactLink\MirroredMilestoneArtifactLinkType;

final class MirrorMilestoneFieldsDataTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItReturnsFieldsDataAsArrayForArtifactCreator(): void
    {
        $copied_values = $this->buildCopiedValues();
        $target_fields = $this->buildSynchronizedFields();

        $fields_data = MirrorMilestoneFieldsData::fromCopiedValuesAndSynchronizedFields($copied_values, $target_fields);

        self::assertEquals(
            [
                1001 => ['new_values' => '112', 'natures' => ['112' => MirroredMilestoneArtifactLinkType::ART_LINK_SHORT_NAME]],
                1002 => 'Aggregator Release',
                1003 => ['content' => '<p>Description</p>', 'format' => 'html'],
            ],
            $fields_data->toFieldsDataArray()
        );
    }

    private function buildCopiedValues(): CopiedValues
    {
        $project = \Project::buildForTest();
        $tracker = $this->buildTestTracker(89, $project);
        $title_field = M::mock(\Tracker_FormElement_Field::class);
        $title_field->shouldReceive('getTracker')->andReturn($tracker);
        $title_changeset_value = new \Tracker_Artifact_ChangesetValue_String(10000, M::mock(\Tracker_Artifact_Changeset::class), $title_field, true, 'Aggregator Release', 'text');

        $description_field = M::mock(\Tracker_FormElement_Field::class);
        $description_field->shouldReceive('getTracker')->andReturn($tracker);
        $description_changeset_value = new \Tracker_Artifact_ChangesetValue_Text(10001, M::mock(\Tracker_Artifact_Changeset::class), $description_field, true, '<p>Description</p>', 'html');

        return new CopiedValues($title_changeset_value, $description_changeset_value, 123456789, 112);
    }

    private function buildSynchronizedFields(): SynchronizedFields
    {
        return new SynchronizedFields(
            new \Tracker_FormElement_Field_ArtifactLink(1001, 89, 1000, 'art_link', 'Links', 'Irrelevant', true, 'P', false, '', 1),
            new \Tracker_FormElement_Field_String(1002, 89, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2),
            new \Tracker_FormElement_Field_Text(1003, 89, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3),
            new \Tracker_FormElement_Field_Selectbox(1004, 89, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4),
            TimeframeFields::fromStartAndEndDates(
                $this->buildTestDateField(1005),
                $this->buildTestDateField(1006)
            )
        );
    }

    private function buildTestDateField(int $id): \Tracker_FormElement_Field_Date
    {
        return new \Tracker_FormElement_Field_Date($id, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 1);
    }

    private function buildTestTracker(int $tracker_id, \Project $project): \Tracker
    {
        $tracker = new \Tracker(
            $tracker_id,
            $project->getID(),
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
        $tracker->setProject($project);
        return $tracker;
    }
}
