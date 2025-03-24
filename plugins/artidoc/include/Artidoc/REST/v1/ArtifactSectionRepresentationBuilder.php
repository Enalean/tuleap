<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\Tracker\Artifact\GetFileUploadData;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFileFullRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\Artifact\FileInfoRepresentation;

final readonly class ArtifactSectionRepresentationBuilder implements BuildArtifactSectionRepresentation
{
    public function __construct(
        private GetFileUploadData $file_upload_data_provider,
        private BuildSectionFields $section_fields_builder,
    ) {
    }

    public function build(
        RequiredArtifactInformation $artifact_information,
        SectionIdentifier $section_identifier,
        Level $level,
        \PFUser $user,
    ): ArtifactSectionRepresentation {
        $can_user_edit_section = $artifact_information->title_field->userCanUpdate($user)
            && $artifact_information->description_field->userCanUpdate($user);

        $artifact = $artifact_information->last_changeset->getArtifact();

        $file_upload_data = $this->file_upload_data_provider->getFileUploadData($artifact->getTracker(), $artifact, $user);

        $attachments = null;
        if ($file_upload_data) {
            $rest = $file_upload_data->getField()->getRESTValue($user, $artifact_information->last_changeset)
                ?? ArtifactFieldValueFileFullRepresentation::fromEmptyValues($file_upload_data->getField());

            $attachments = new ArtifactSectionAttachmentsRepresentation(
                $file_upload_data->getUploadUrl(),
                array_values(
                    array_map(
                        static fn (FileInfoRepresentation $file_info_representation): int => $file_info_representation->id,
                        $rest->file_descriptions,
                    ),
                ),
            );
        }

        return new ArtifactSectionRepresentation(
            $section_identifier->toString(),
            $level->value,
            ArtifactReference::build($artifact),
            $artifact_information->title,
            $artifact_information->description,
            $can_user_edit_section,
            $attachments,
            $this->section_fields_builder->getFields($artifact_information->last_changeset),
        );
    }
}
