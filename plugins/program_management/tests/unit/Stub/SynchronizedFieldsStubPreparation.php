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

namespace Tuleap\ProgramManagement\Tests\Stub;

final class SynchronizedFieldsStubPreparation
{
    public TitleFieldReferenceStub $title;
    public DescriptionFieldReferenceStub $description;
    public StatusFieldReferenceStub $status;
    public StartDateFieldReferenceStub $start_date;
    public EndPeriodFieldReferenceStub $end_period;
    public ArtifactLinkFieldReferenceStub $artifact_link;

    public function __construct(
        int $title_field_id,
        int $description_field_id,
        int $status_field_id,
        int $start_date_field_id,
        int $end_period_field_id,
        int $artifact_link_field_id
    ) {
        $this->title         = TitleFieldReferenceStub::withId($title_field_id);
        $this->description   = DescriptionFieldReferenceStub::withId($description_field_id);
        $this->status        = StatusFieldReferenceStub::withId($status_field_id);
        $this->start_date    = StartDateFieldReferenceStub::withId($start_date_field_id);
        $this->end_period    = EndPeriodFieldReferenceStub::withId($end_period_field_id);
        $this->artifact_link = ArtifactLinkFieldReferenceStub::withId($artifact_link_field_id);
    }
}
