<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\EndPeriodValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\MappedStatusValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StatusValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TitleValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\ProgramIncrementFieldsData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldsData;

final class ProgramIncrementFieldsDataTest extends \PHPUnit\Framework\TestCase
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

        $fields_data = ProgramIncrementFieldsData::fromSourceChangesetValuesAndSynchronizedFields(
            $copied_values,
            $mapped_status_value,
            $target_fields
        );

        self::assertEquals(
            [
                1001 => ['new_values' => '112', 'natures' => ['112' => ProgramIncrementArtifactLinkType::ART_LINK_SHORT_NAME]],
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
    ): SourceChangesetValuesCollection {
        $list_values = [];
        foreach ($status_value as $bind_value_id) {
            $list_values[] = new \Tracker_FormElement_Field_List_Bind_StaticValue(
                $bind_value_id,
                'Irrelevant',
                'Irrelevant',
                0,
                0
            );
        }

        $title_value         = new TitleValueData($title_value);
        $description_value   = new DescriptionValueData($description_value, $description_format);
        $status_value        = new StatusValueData($list_values);
        $start_date_value    = new StartDateValueData('2020-10-01');
        $end_period_value    = new EndPeriodValueData('2020-10-10');
        $artifact_link_value = new ArtifactLinkValueData($program_artifact_id);

        return new SourceChangesetValuesCollection(
            $program_artifact_id,
            $title_value,
            $description_value,
            $status_value,
            123456789,
            $start_date_value,
            $end_period_value,
            $artifact_link_value
        );
    }

    private function buildSynchronizedFields(
        int $artifact_link_id,
        int $title_id,
        int $description_id,
        int $status_id,
        int $start_date_id,
        int $end_date_id
    ): SynchronizedFieldsData {
        $artifact_link_field_data = new FieldData(new \Tracker_FormElement_Field_ArtifactLink($artifact_link_id, 89, 1000, 'art_link', 'Links', 'Irrelevant', true, 'P', false, '', 1));

        $title_field_data = new FieldData(new \Tracker_FormElement_Field_String($title_id, 89, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2));

        $description_field_data = new FieldData(new \Tracker_FormElement_Field_Text($description_id, 89, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3));

        $status_field_data = new FieldData(new \Tracker_FormElement_Field_Selectbox($status_id, 89, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4));

        $start_date_field_data = new FieldData(new \Tracker_FormElement_Field_Date($start_date_id, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 5));

        $end_date_field_data = new FieldData(new \Tracker_FormElement_Field_Date($end_date_id, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 6));

        return new SynchronizedFieldsData(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );
    }
}
