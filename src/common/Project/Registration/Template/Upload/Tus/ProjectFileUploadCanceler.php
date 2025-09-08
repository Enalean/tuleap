<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\Registration\Template\Upload\Tus;

use Tuleap\Project\Registration\Template\Upload\DeleteFileUpload;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Tus\TusTerminaterDataStore;
use Tuleap\Upload\UploadPathAllocator;

final readonly class ProjectFileUploadCanceler implements TusTerminaterDataStore
{
    public function __construct(private UploadPathAllocator $path_allocator, private DeleteFileUpload $file_ongoing_upload_dao)
    {
    }

    #[\Override]
    public function terminateUpload(TusFileInformation $file_information): void
    {
        $this->file_ongoing_upload_dao->deleteByID($file_information);
        $file_path = $this->path_allocator->getPathForItemBeingUploaded($file_information);
        if (is_file($file_path)) {
            @unlink($file_path);
        }
    }
}
