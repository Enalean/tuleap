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

namespace Tuleap\TestManagement\REST;

use Luracast\Restler\RestException;
use PFUser;
use Tracker_Artifact_ChangesetValue_File;
use Tracker_FileInfo;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

class FormattedChangesetValueForFileFieldRetriever
{
    /**
     * @var FileUploadDataProvider
     */
    private $file_upload_data_provider;

    public function __construct(FileUploadDataProvider $file_upload_data_provider)
    {
        $this->file_upload_data_provider = $file_upload_data_provider;
    }

    /**
     * @throws RestException
     */
    public function getFormattedChangesetValueForFieldFile(
        array $uploaded_file_ids,
        Artifact $artifact,
        PFUser $user
    ): ArtifactValuesRepresentation {
        $field_upload_data = $this->file_upload_data_provider->getFileUploadData(
            $artifact->getTracker(),
            $artifact,
            $user
        );

        if (! $field_upload_data) {
            throw new RestException(
                400,
                "There is no file field that you can update in your tracker. You can't add an image."
            );
        }

        $values_representation           = new ArtifactValuesRepresentation();
        $values_representation->field_id = (int) $field_upload_data->getField()->getId();

        $changeset_value = $field_upload_data->getField()->getLastChangesetValue($artifact);
        $field_values_id = [];
        if ($changeset_value instanceof Tracker_Artifact_ChangesetValue_File) {
            $field_values_id = $this->getExistingFilesIdFromChangesetValue($changeset_value);
        }


        $values_representation->value = array_merge($uploaded_file_ids, $field_values_id);

        return $values_representation;
    }

    private function getExistingFilesIdFromChangesetValue(Tracker_Artifact_ChangesetValue_File $changeset_value): array
    {
        $file_values_id = [];
        foreach ($changeset_value->getFiles() as $file) {
            \assert($file instanceof Tracker_FileInfo);
            $file_values_id[] = (int) $file->getId();
        }

        return $file_values_id;
    }
}
