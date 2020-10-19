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

namespace Tuleap\ScaledAgile\Program\Milestone;

final class SynchronizedFieldsTest extends \PHPUnit\Framework\TestCase
{
    public function testItReturnsAnArrayOfFields(): void
    {
        $artifact_link_field = new \Tracker_FormElement_Field_ArtifactLink(2, 36, 1, 'art_link', 'Artifact links', 'Irrelevant', true, 'P', false, '', 1);
        $title_field         = new \Tracker_FormElement_Field_String(3, 36, 1, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2);
        $description_field   = new \Tracker_FormElement_Field_Text(4, 36, 1, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3);
        $status_field        = new \Tracker_FormElement_Field_Selectbox(5, 36, 1, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4);
        $start_date_field    = new \Tracker_FormElement_Field_Date(6, 36, 1, 'start_date', 'Start Date', 'Irrelevant', true, 'P', false, '', 5);
        $duration_field      = new \Tracker_FormElement_Field_Integer(7, 36, 1, 'duration', 'Duration (in days)', 'Irrelevant', true, 'P', false, '', 6);

        $fields = new SynchronizedFields(
            $artifact_link_field,
            $title_field,
            $description_field,
            $status_field,
            TimeframeFields::fromStartDateAndDuration($start_date_field, $duration_field)
        );

        $fields_array = $fields->toArrayOfFields();
        self::assertContains($artifact_link_field, $fields_array);
        self::assertContains($title_field, $fields_array);
        self::assertContains($description_field, $fields_array);
        self::assertContains($status_field, $fields_array);
        self::assertContains($start_date_field, $fields_array);
        self::assertContains($duration_field, $fields_array);
    }
}
