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

use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Project\Registration\Template\Upload\DeleteFileUpload;
use Tuleap\Project\Registration\Template\Upload\FinishFileUploadPostAction;
use Tuleap\Project\Registration\Template\Upload\SearchFileUpload;
use Tuleap\Request\ForbiddenException;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Tus\TusFinisherDataStore;
use Tuleap\Upload\UploadPathAllocator;
use Tuleap\User\ProvideCurrentRequestUser;

final readonly class ProjectFileUploadFinisher implements TusFinisherDataStore
{
    public function __construct(
        private DeleteFileUpload $file_ongoing_upload_dao,
        private SearchFileUpload $search_file_upload,
        private UploadPathAllocator $upload_path_allocator,
        private FinishFileUploadPostAction $finish_file_upload_post_action,
        private ProvideCurrentRequestUser $current_user_request,
    ) {
    }

    #[\Override]
    public function finishUpload(ServerRequestInterface $request, TusFileInformation $file_information): void
    {
        $file_path = $this->upload_path_allocator->getPathForItemBeingUploaded($file_information);
        try {
            $user = $this->current_user_request->getCurrentRequestUser($request);
            if (! $user) {
                throw new ForbiddenException();
            }

            $this->finish_file_upload_post_action->process(
                $this->getProjectId($file_information),
                $file_path,
                (int) $user->getId(),
            );
        } finally {
            $this->file_ongoing_upload_dao->deleteById($file_information);
        }
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
