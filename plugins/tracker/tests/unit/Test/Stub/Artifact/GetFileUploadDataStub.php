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

namespace Tuleap\Tracker\Test\Stub\Artifact;

use PFUser;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\FileUploadData;
use Tuleap\Tracker\Artifact\GetFileUploadData;
use Tuleap\Tracker\FormElement\Field\Files\FilesField;
use Tuleap\Tracker\Tracker;

final class GetFileUploadDataStub implements GetFileUploadData
{
    private function __construct(private readonly ?FileUploadData $file_upload_data)
    {
    }

    public static function withoutField(): self
    {
        return new self(null);
    }

    public static function withField(FilesField $field): self
    {
        return new self(new FileUploadData($field));
    }

    #[\Override]
    public function getFileUploadData(Tracker $tracker, ?Artifact $artifact, PFUser $user): ?FileUploadData
    {
        return $this->file_upload_data;
    }
}
