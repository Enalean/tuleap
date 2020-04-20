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

namespace Tuleap\Tracker\Artifact;

use ForgeConfig;
use Tracker_FormElement_Field_File;

class FileUploadData
{
    /**
     * @var Tracker_FormElement_Field_File
     */
    private $field;
    /**
     * @var string
     */
    private $upload_url;
    /**
     * @var string
     */
    private $upload_file_name;

    /**
     * @var int
     */
    private $upload_max_size;

    public function __construct(Tracker_FormElement_Field_File $field)
    {
        $this->field            = $field;
        $this->upload_url       = '/api/v1/tracker_fields/' . $field->getId() . '/files';
        $this->upload_file_name = 'artifact[' . (int) $field->getId() . '][][tus-uploaded-id]';
        $this->upload_max_size  = ForgeConfig::get('sys_max_size_upload');
    }

    public function getField(): Tracker_FormElement_Field_File
    {
        return $this->field;
    }

    public function getUploadUrl(): string
    {
        return $this->upload_url;
    }

    public function getUploadFileName(): string
    {
        return $this->upload_file_name;
    }

    public function getUploadMaxSize(): int
    {
        return $this->upload_max_size;
    }
}
