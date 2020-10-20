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

namespace Tuleap\ScaledAgile\Program\Milestone\Mirroring;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ScaledAgile\Program\Milestone\Mirroring\Status\MappedStatusValue;
use Tuleap\ScaledAgile\Program\Milestone\SynchronizedFields;
use Tuleap\ScaledAgile\Program\Milestone\TimeframeFields;
use Tuleap\ScaledAgile\Program\MirroredArtifactLink\MirroredMilestoneArtifactLinkType;

final class MirrorMilestoneFieldsDataTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItReturnsFieldsDataAsArrayForArtifactCreator(): void
    {
        $copied_values       = $this->buildCopiedValues(
            'Program Release',
            '<p>Description</p>',
            'html',
            [2001],
            112
        );
        $mapped_status_value = new MappedStatusValue([3001]);
        $target_fields       = $this->buildSynchronizedFields(1001, 1002, 1003, 1004, 1005, 1006);

        $fields_data = MirrorMilestoneFieldsData::fromCopiedValuesAndSynchronizedFields(
            $copied_values,
            $mapped_status_value,
            $target_fields
        );

        self::assertEquals(
            [
                1001 => ['new_values' => '112', 'natures' => ['112' => MirroredMilestoneArtifactLinkType::ART_LINK_SHORT_NAME]],
                1002 => 'Program Release',
                1003 => ['content' => '<p>Description</p>', 'format' => 'html'],
                1004 => [3001],
                1005 => '2020-10-01',
                1006 => '2020-10-10'
            ],
            $fields_data->toFieldsDataArray()
        );
    }

    /**
     * @param int[]  $status_value
     */
    private function buildCopiedValues(
        string $title_value,
        string $description_value,
        string $description_format,
        array $status_value,
        int $program_artifact_id
    ): CopiedValues {
        $project = \Project::buildForTest();
        $tracker = $this->buildTestTracker(89, $project);
        $title_field = M::mock(\Tracker_FormElement_Field::class);
        $title_field->shouldReceive('getTracker')->andReturn($tracker);
        $title_changeset_value = new \Tracker_Artifact_ChangesetValue_String(10000, M::mock(\Tracker_Artifact_Changeset::class), $title_field, true, $title_value, 'text');

        $description_field = M::mock(\Tracker_FormElement_Field::class);
        $description_field->shouldReceive('getTracker')->andReturn($tracker);
        $description_changeset_value = new \Tracker_Artifact_ChangesetValue_Text(10001, M::mock(\Tracker_Artifact_Changeset::class), $description_field, true, $description_value, $description_format);

        $list_values = [];
        foreach ($status_value as $bind_value_id) {
            $list_values[] = new \Tracker_FormElement_Field_List_Bind_StaticValue($bind_value_id, 'Irrelevant', 'Irrelevant', 0, 0);
        }
        $status_changeset_value = new \Tracker_Artifact_ChangesetValue_List(
            10002,
            M::mock(\Tracker_Artifact_Changeset::class),
            M::mock(\Tracker_FormElement_Field::class),
            true,
            $list_values
        );

        $field_start_date                      = M::mock(\Tracker_FormElement_Field::class);
        $field_start_date->shouldReceive('formatDateForDisplay')->andReturn('2020-10-01');
        $start_date_changeset_value = new \Tracker_Artifact_ChangesetValue_Date(
            100003,
            M::mock(\Tracker_Artifact_Changeset::class),
            $field_start_date,
            true,
            1285891200
        );

        $field_end_period                      = M::mock(\Tracker_FormElement_Field_Date::class);
        $field_end_period->shouldReceive('formatDateForDisplay')->andReturn('2020-10-10');
        $end_period_changeset_value = new \Tracker_Artifact_ChangesetValue_Date(
            100004,
            M::mock(\Tracker_Artifact_Changeset::class),
            $field_end_period,
            true,
            1602288000
        );

        return new CopiedValues(
            $title_changeset_value,
            $description_changeset_value,
            $status_changeset_value,
            123456789,
            $program_artifact_id,
            $start_date_changeset_value,
            $end_period_changeset_value
        );
    }

    private function buildSynchronizedFields(
        int $artifact_link_field_id,
        int $title_field_id,
        int $description_field_id,
        int $status_field_id,
        int $start_date_field_id,
        int $end_date_field_id
    ): SynchronizedFields {
        return new SynchronizedFields(
            new \Tracker_FormElement_Field_ArtifactLink($artifact_link_field_id, 89, 1000, 'art_link', 'Links', 'Irrelevant', true, 'P', false, '', 1),
            new \Tracker_FormElement_Field_String($title_field_id, 89, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2),
            new \Tracker_FormElement_Field_Text($description_field_id, 89, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3),
            new \Tracker_FormElement_Field_Selectbox($status_field_id, 89, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4),
            TimeframeFields::fromStartAndEndDates(
                $this->buildTestDateField($start_date_field_id),
                $this->buildTestDateField($end_date_field_id)
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
