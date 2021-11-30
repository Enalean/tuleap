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
    private function __construct(
        public ?TitleFieldReferenceStub $title,
        public ?DescriptionFieldReferenceStub $description,
        public ?StatusFieldReferenceStub $status,
        public ?StartDateFieldReferenceStub $start_date,
        public ?EndDateFieldReferenceStub $end_date,
        public ?DurationFieldReferenceStub $duration,
        public ?ArtifactLinkFieldReferenceStub $artifact_link,
    ) {
    }

    public static function withAllFields(
        int $title_field_id,
        int $description_field_id,
        int $status_field_id,
        int $start_date_field_id,
        int $end_date_field_id,
        int $artifact_link_field_id,
    ): self {
        return new self(
            TitleFieldReferenceStub::withId($title_field_id),
            DescriptionFieldReferenceStub::withId($description_field_id),
            StatusFieldReferenceStub::withId($status_field_id),
            StartDateFieldReferenceStub::withId($start_date_field_id),
            EndDateFieldReferenceStub::withId($end_date_field_id),
            null,
            ArtifactLinkFieldReferenceStub::withId($artifact_link_field_id)
        );
    }

    public static function withDuration(
        int $title_field_id,
        int $description_field_id,
        int $status_field_id,
        int $start_date_field_id,
        int $duration_field_id,
        int $artifact_link_field_id,
    ): self {
        return new self(
            TitleFieldReferenceStub::withId($title_field_id),
            DescriptionFieldReferenceStub::withId($description_field_id),
            StatusFieldReferenceStub::withId($status_field_id),
            StartDateFieldReferenceStub::withId($start_date_field_id),
            null,
            DurationFieldReferenceStub::withId($duration_field_id),
            ArtifactLinkFieldReferenceStub::withId($artifact_link_field_id)
        );
    }

    public static function withOnlyArtifactLinkField(int $artifact_link_field_id): self
    {
        return new self(
            null,
            null,
            null,
            null,
            null,
            null,
            ArtifactLinkFieldReferenceStub::withId($artifact_link_field_id)
        );
    }
}
