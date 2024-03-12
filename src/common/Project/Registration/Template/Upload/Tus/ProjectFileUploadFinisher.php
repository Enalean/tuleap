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
use Tuleap\Project\Registration\Template\Upload\FinishFileUploadPostAction;
use Tuleap\Project\Registration\Template\Upload\SearchFileUpload;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Tus\TusFinisherDataStore;
use Tuleap\Upload\UploadPathAllocator;

final readonly class ProjectFileUploadFinisher implements TusFinisherDataStore
{
    public function __construct(
        private DeleteFileUpload $file_ongoing_upload_dao,
        private SearchFileUpload $search_file_upload,
        private UploadPathAllocator $upload_path_allocator,
        private FinishFileUploadPostAction $finish_file_upload_post_action,
    ) {
    }

    public function finishUpload(TusFileInformation $file_information): void
    {
        $file_path = $this->upload_path_allocator->getPathForItemBeingUploaded($file_information);
        try {
            $this->tryToOpenArchive($file_path);
            $this->finish_file_upload_post_action->process($this->getProjectId($file_information), $file_path);
        } finally {
            $this->file_ongoing_upload_dao->deleteById($file_information);
        }
    }

    /**
     * @throws FileIsNotAnArchiveException
     */
    private function tryToOpenArchive(string $file_path): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($file_path) !== true) {
            unlink($file_path);
            throw new FileIsNotAnArchiveException();
        }
        $zip->close();
    }

    private function getProjectId(TusFileInformation $file_information): int
    {
        $row = $this->search_file_upload->searchFileOngoingUploadById($file_information->getID());

        if (isset($row['project_id'])) {
            return $row['project_id'];
        }

        throw new ProjectNotFoundException($file_information);
    }
}
