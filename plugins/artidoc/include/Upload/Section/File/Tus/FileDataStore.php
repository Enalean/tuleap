<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Upload\Section\File\Tus;

use Tuleap\Tus\NextGen\TusDataStore;
use Tuleap\Tus\NextGen\TusFileInformationProvider;
use Tuleap\Tus\NextGen\TusFinisherDataStore;
use Tuleap\Tus\NextGen\TusLocker;
use Tuleap\Tus\NextGen\TusTerminaterDataStore;
use Tuleap\Tus\NextGen\TusWriter;

final readonly class FileDataStore implements TusDataStore
{
    public function __construct(
        private FileBeingUploadedInformationProvider $file_being_uploaded_information_provider,
        private ArtidocFileBeingUploadedWriter $file_being_uploaded_writer,
        private FileUploadFinisher $file_upload_finisher,
        private FileUploadCanceler $file_upload_canceler,
        private ArtidocFileBeingUploadedLocker $file_being_uploaded_locker,
    ) {
    }

    public function getFileInformationProvider(): TusFileInformationProvider
    {
        return $this->file_being_uploaded_information_provider;
    }

    public function getWriter(): TusWriter
    {
        return $this->file_being_uploaded_writer;
    }

    public function getFinisher(): ?TusFinisherDataStore
    {
        return $this->file_upload_finisher;
    }

    public function getTerminater(): ?TusTerminaterDataStore
    {
        return $this->file_upload_canceler;
    }

    public function getLocker(): ?TusLocker
    {
        return $this->file_being_uploaded_locker;
    }
}
