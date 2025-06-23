<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Adapter\Document\Section\Artifact;

use Tuleap\Artidoc\Adapter\Document\Section\UpdateLevel;
use Tuleap\Artidoc\Domain\Document\Section\Artifact\ArtifactContent;
use Tuleap\Artidoc\Domain\Document\Section\Artifact\UpdateArtifactContent;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\GetFileUploadData;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\REST\Artifact\HandlePUT;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\Semantic\Description\RetrieveSemanticDescriptionField;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;

final readonly class ArtifactContentUpdater implements UpdateArtifactContent
{
    public function __construct(
        private RetrieveArtifact $artifact_retriever,
        private GetFileUploadData $file_upload_data_provider,
        private UpdateLevel $level_updater,
        private HandlePUT $put_handler,
        private RetrieveSemanticDescriptionField $retrieve_description_field,
        private \PFUser $current_user,
    ) {
    }

    public function updateArtifactContent(
        SectionIdentifier $section_identifier,
        int $artifact_id,
        ArtifactContent $content,
    ): Ok|Err {
        return $this->delegateUpdateOfArtifactToTrackerAPI($artifact_id, $content)
            ->andThen(function () use ($section_identifier, $content) {
                $this->level_updater->updateLevel($section_identifier, $content->level);

                return Result::ok(null);
            });
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function delegateUpdateOfArtifactToTrackerAPI(int $artifact_id, ArtifactContent $content): Ok|Err
    {
        $artifact = $this->artifact_retriever->getArtifactById($artifact_id);
        if (! $artifact || ! $artifact->userCanUpdate($this->current_user)) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'User cannot update artifact #%s',
                    $artifact_id,
                )
            ));
        }

        $title_field = TrackerSemanticTitle::load($artifact->getTracker())->getField();
        if (! $title_field) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'There is no title field for artifact #%s',
                    $artifact_id,
                )
            ));
        }
        if (! $title_field->userCanUpdate($this->current_user)) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'User cannot update title of artifact #%s',
                    $artifact_id,
                )
            ));
        }

        $description_field = $this->retrieve_description_field->fromTracker($artifact->getTracker());
        if (! $description_field) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'There is no description field for artifact #%s',
                    $artifact_id,
                )
            ));
        }
        if (! $description_field->userCanUpdate($this->current_user)) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'User cannot update description of artifact #%s',
                    $artifact_id,
                )
            ));
        }

        $title_value           = new ArtifactValuesRepresentation();
        $title_value->field_id = $title_field->getId();
        $title_value->value    = $title_field instanceof \Tracker_FormElement_Field_String
            ? $content->title
            : [
                'content' => $content->title,
                'format'  => 'text',
            ];

        $description_value           = new ArtifactValuesRepresentation();
        $description_value->field_id = $description_field->getId();
        $description_value->value    = [
            'content' => $content->description,
            'format'  => 'html',
        ];

        $values = [
            $title_value,
            $description_value,
        ];

        $file_upload_data = $this->file_upload_data_provider->getFileUploadData($artifact->getTracker(), $artifact, $this->current_user);
        if ($file_upload_data) {
            $attachment_value           = new ArtifactValuesRepresentation();
            $attachment_value->field_id = $file_upload_data->getField()->getId();
            $attachment_value->value    = $content->attachments;

            $values[] = $attachment_value;
        }

        $this->put_handler->handle(
            $values,
            $artifact,
            $this->current_user,
            null,
        );

        return Result::ok(null);
    }
}
