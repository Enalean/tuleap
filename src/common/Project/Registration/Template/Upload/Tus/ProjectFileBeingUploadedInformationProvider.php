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

use DateTimeImmutable;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Project\Registration\CheckProjectRegistrationUserPermission;
use Tuleap\Project\Registration\RegistrationForbiddenException;
use Tuleap\Project\Registration\Template\Upload\SearchFileUploadByExpirationDate;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Tus\TusFileInformationProvider;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\UploadPathAllocator;
use Tuleap\User\ProvideCurrentRequestUser;

final readonly class ProjectFileBeingUploadedInformationProvider implements TusFileInformationProvider
{
    public function __construct(
        private UploadPathAllocator $path_allocator,
        private SearchFileUploadByExpirationDate $file_ongoing_upload_dao,
        private ProvideCurrentRequestUser $current_user_request,
        private CheckProjectRegistrationUserPermission $permission_checker,
    ) {
    }

    /**
     * @throws NotFoundException
     * @throws \Tuleap\Request\ForbiddenException
     */
    public function getFileInformation(ServerRequestInterface $request): ?TusFileInformation
    {
        $id = $request->getAttribute('id');

        if ($id === null) {
            return null;
        }

        $current_user = $this->current_user_request->getCurrentRequestUser($request);
        if ($current_user === null) {
            throw new NotFoundException();
        }

        try {
            $this->permission_checker->checkUserCreateAProject($current_user);
        } catch (RegistrationForbiddenException $exception) {
            throw new ForbiddenException($exception->getMessage());
        }

        $file_row = $this->file_ongoing_upload_dao->searchFileOngoingUploadedByIdAndUserIdAndExpirationDate(
            (int) $id,
            (int) $current_user->getId(),
            new DateTimeImmutable()
        );

        if (empty($file_row)) {
            return null;
        }

        $file_ongoing_upload_information = FileOngoingUploadInformation::fromDB($file_row);

        return new FileBeingUploadedInformation(
            $file_ongoing_upload_information->id,
            $file_ongoing_upload_information->file_name,
            $file_ongoing_upload_information->file_size,
            $this->getCurrentFileSize($file_ongoing_upload_information)
        );
    }

    private function getCurrentFileSize(FileOngoingUploadInformation $file_ongoing_upload_information): int
    {
        $temporary_file_information = new FileBeingUploadedInformation(
            $file_ongoing_upload_information->id,
            $file_ongoing_upload_information->file_name,
            $file_ongoing_upload_information->file_size,
            0
        );

        $allocated_path = $this->path_allocator->getPathForItemBeingUploaded(
            $temporary_file_information
        );

        return file_exists($allocated_path) ? filesize($allocated_path) : 0;
    }
}
