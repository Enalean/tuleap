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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\MappedStatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\Field;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\ProgramIncrementFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Tests\Builder\SourceChangesetValuesCollectionBuilder;

final class ProgramIncrementFieldsDataTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsFieldsDataAsArrayForArtifactCreator(): void
    {
        $copied_values       = SourceChangesetValuesCollectionBuilder::buildWithValues(
            'Program Release',
            '<p>Description</p>',
            'html',
            [2001],
            '2020-10-01',
            '2020-10-10',
            112
        );
        $mapped_status_value = new MappedStatusValue([3001]);
        $target_fields       = $this->buildSynchronizedFields(1001, 1002, 1003, 1004, 1005, 1006);

        $fields_data = ProgramIncrementFields::fromSourceChangesetValuesAndSynchronizedFields(
            $copied_values,
            $mapped_status_value,
            $target_fields
        );

        self::assertEquals(
            [
                1001 => ['new_values' => '112', 'natures' => ['112' => TimeboxArtifactLinkType::ART_LINK_SHORT_NAME]],
                1002 => 'Program Release',
                1003 => ['content' => '<p>Description</p>', 'format' => 'html'],
                1004 => [3001],
                1005 => '2020-10-01',
                1006 => '2020-10-10'
            ],
            $fields_data->toFieldsDataArray()
        );
    }

    private function buildSynchronizedFields(
        int $artifact_link_id,
        int $title_id,
        int $description_id,
        int $status_id,
        int $start_date_id,
        int $end_date_id
    ): SynchronizedFields {
        $artifact_link_field_data = new Field(new \Tracker_FormElement_Field_ArtifactLink($artifact_link_id, 89, 1000, 'art_link', 'Links', 'Irrelevant', true, 'P', false, '', 1));

        $title_field_data = new Field(new \Tracker_FormElement_Field_String($title_id, 89, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2));

        $description_field_data = new Field(new \Tracker_FormElement_Field_Text($description_id, 89, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3));

        $status_field_data = new Field(new \Tracker_FormElement_Field_Selectbox($status_id, 89, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4));

        $start_date_field_data = new Field(new \Tracker_FormElement_Field_Date($start_date_id, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 5));

        $end_date_field_data = new Field(new \Tracker_FormElement_Field_Date($end_date_id, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 6));

        return new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );
    }
}
