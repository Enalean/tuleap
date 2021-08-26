<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\Field;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;

final class SynchronizedFieldsBuilder
{
    private const TRACKER_ID = 89;

    public static function build(): SynchronizedFields
    {
        return self::buildWithIds(1991, 1376, 1412, 1499, 1784, 1368);
    }

    public static function buildWithFields(
        \Tracker_FormElement_Field_String $title_field,
        \Tracker_FormElement_Field_Text $description_field,
        \Tracker_FormElement_Field_Selectbox $status_field,
        \Tracker_FormElement_Field_Date $start_date_field,
        \Tracker_FormElement_Field_Date $end_period_field
    ): SynchronizedFields {
        $artifact_link_field_data = new Field(
            new \Tracker_FormElement_Field_ArtifactLink(1991, 89, 1000, 'art_link', 'Links', 'Irrelevant', true, 'P', false, '', 1)
        );

        $title_field_data       = new Field($title_field);
        $description_field_data = new Field($description_field);
        $status_field_data      = new Field($status_field);
        $start_date_field_data  = new Field($start_date_field);
        $end_date_field_data    = new Field($end_period_field);

        return new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );
    }

    public static function buildWithIds(
        int $artifact_link_id,
        int $title_id,
        int $description_id,
        int $status_id,
        int $start_date_id,
        int $end_date_id
    ): SynchronizedFields {
        $artifact_link_field_data = new Field(
            new \Tracker_FormElement_Field_ArtifactLink($artifact_link_id, self::TRACKER_ID, 1000, 'art_link', 'Links', 'Irrelevant', true, 'P', false, '', 1)
        );

        $title_field_data = new Field(
            new \Tracker_FormElement_Field_String($title_id, self::TRACKER_ID, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2)
        );

        $description_field_data = new Field(
            new \Tracker_FormElement_Field_Text($description_id, self::TRACKER_ID, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3)
        );

        $status_field_data = new Field(
            new \Tracker_FormElement_Field_Selectbox($status_id, self::TRACKER_ID, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4)
        );

        $start_date_field_data = new Field(
            new \Tracker_FormElement_Field_Date($start_date_id, self::TRACKER_ID, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 5)
        );

        $end_date_field_data = new Field(
            new \Tracker_FormElement_Field_Date($end_date_id, self::TRACKER_ID, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 6)
        );

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
