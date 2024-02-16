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

use Tuleap\Tus\TusDataStore;
use Tuleap\Tus\TusFileInformationProvider;
use Tuleap\Tus\TusFinisherDataStore;
use Tuleap\Tus\TusLocker;
use Tuleap\Tus\TusTerminaterDataStore;
use Tuleap\Tus\TusWriter;
use Tuleap\Upload\FileBeingUploadedLocker;
use Tuleap\Upload\FileBeingUploadedWriter;

final readonly class ProjectFileDataStore implements TusDataStore
{
    public function __construct(
        private ProjectFileBeingUploadedInformationProvider $project_archive_being_uploaded_information_provider,
        private FileBeingUploadedWriter $project_archive_being_uploaded_writer,
        private ProjectFileUploadFinisher $project_archive_upload_finisher,
        private ProjectFileUploadCanceler $project_archive_upload_canceler,
        private FileBeingUploadedLocker $project_archive_being_uploaded_locker,
    ) {
    }

    public function getFileInformationProvider(): TusFileInformationProvider
    {
        return $this->project_archive_being_uploaded_information_provider;
    }

    public function getWriter(): TusWriter
    {
        return $this->project_archive_being_uploaded_writer;
    }

    public function getFinisher(): ?TusFinisherDataStore
    {
        return $this->project_archive_upload_finisher;
    }

    public function getTerminater(): ?TusTerminaterDataStore
    {
        return $this->project_archive_upload_canceler;
    }

    public function getLocker(): ?TusLocker
    {
        return $this->project_archive_being_uploaded_locker;
    }
}
