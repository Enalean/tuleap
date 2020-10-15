<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use PFUser;
use Tracker;

class UploadDataAttributesForRichTextEditorBuilder
{
    /**
     * @var FileUploadDataProvider
     */
    private $file_upload_data_provider;

    public function __construct(
        FileUploadDataProvider $file_upload_data_provider
    ) {
        $this->file_upload_data_provider = $file_upload_data_provider;
    }

    public function getDataAttributes(Tracker $tracker, PFUser $user, ?Artifact $artifact): array
    {
        $data_attributes = [];

        $field_upload_data = $this->file_upload_data_provider->getFileUploadData(
            $tracker,
            $artifact,
            $user
        );
        if ($field_upload_data) {
            $data_attributes[] = [
                'name' => 'upload-url',
                'value' => $field_upload_data->getUploadUrl()
            ];
            $data_attributes[] = [
                'name' => 'upload-field-name',
                'value' => $field_upload_data->getUploadFileName()
            ];
            $data_attributes[] = [
                'name' => 'upload-max-size',
                'value' => $field_upload_data->getUploadMaxSize()
            ];
        }


        return $data_attributes;
    }
}
