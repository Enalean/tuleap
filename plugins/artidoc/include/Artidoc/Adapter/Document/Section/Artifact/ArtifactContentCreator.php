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

use Tuleap\Artidoc\Document\RetrieveConfiguredTracker;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Artifact\ArtifactContent;
use Tuleap\Artidoc\Domain\Document\Section\Artifact\CreateArtifactContent;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\GetFileUploadData;
use Tuleap\Tracker\REST\Artifact\CreateArtifact;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\Semantic\Description\TrackerSemanticDescription;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;

final readonly class ArtifactContentCreator implements CreateArtifactContent
{
    public function __construct(
        private RetrieveConfiguredTracker $tracker_retriever,
        private GetFileUploadData $file_upload_data_provider,
        private CreateArtifact $artifact_creator,
        private \PFUser $current_user,
    ) {
    }

    public function createArtifact(ArtidocWithContext $artidoc, ArtifactContent $content): Ok|Err
    {
        $tracker = $this->tracker_retriever->getTracker($artidoc->document);
        if ($tracker === null) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'Document #%s does not have a configured tracker',
                    $artidoc->document->getId(),
                ),
            ));
        }

        $title_field = TrackerSemanticTitle::load($tracker)->getField();
        if (! $title_field) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'There is no title field for tracker #%s in artidoc #%s',
                    $tracker->getId(),
                    $artidoc->document->getId(),
                ),
            ));
        }
        if (! $title_field->userCanSubmit($this->current_user)) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'User cannot submit title of tracker #%s in artidoc #%s',
                    $tracker->getId(),
                    $artidoc->document->getId(),
                ),
            ));
        }

        $description_field = TrackerSemanticDescription::load($tracker)->getField();
        if (! $description_field) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'There is no description field for tracker #%s in artidoc #%s',
                    $tracker->getId(),
                    $artidoc->document->getId(),
                ),
            ));
        }
        if (! $description_field->userCanSubmit($this->current_user)) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'User cannot submit description of tracker #%s in artidoc #%s',
                    $tracker->getId(),
                    $artidoc->document->getId(),
                ),
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

        if (count($content->attachments) > 0) {
            $file_upload_data = $this->file_upload_data_provider->getFileUploadData($tracker, null, $this->current_user);
            if ($file_upload_data) {
                $attachment_value           = new ArtifactValuesRepresentation();
                $attachment_value->field_id = $file_upload_data->getField()->getId();
                $attachment_value->value    = $content->attachments;

                $values[] = $attachment_value;
            }
        }

        $artifact_reference = $this->artifact_creator->create(
            $this->current_user,
            TrackerReference::build($tracker),
            $values,
            true,
        );

        return Result::ok($artifact_reference->id);
    }
}
