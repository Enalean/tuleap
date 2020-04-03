<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\FRS\Upload\Tus;

use Tuleap\Tus\TusDataStore;
use Tuleap\Tus\TusFileInformationProvider;
use Tuleap\Tus\TusFinisherDataStore;
use Tuleap\Tus\TusLocker;
use Tuleap\Tus\TusTerminaterDataStore;
use Tuleap\Tus\TusWriter;
use Tuleap\Upload\FileBeingUploadedLocker;
use Tuleap\Upload\FileBeingUploadedWriter;

final class FileDataStore implements TusDataStore
{
    /**
     * @var FileBeingUploadedInformationProvider
     */
    private $file_being_uploaded_information_provider;
    /**
     * @var FileBeingUploadedWriter
     */
    private $file_being_uploaded_writer;
    /**
     * @var FileBeingUploadedLocker
     */
    private $file_being_uploaded_locker;
    /**
     * @var FileUploadFinisher
     */
    private $file_upload_finisher;
    /**
     * @var FileUploadCanceler
     */
    private $file_upload_canceler;

    public function __construct(
        FileBeingUploadedInformationProvider $file_being_uploaded_information_provider,
        FileBeingUploadedWriter $file_being_uploaded_writer,
        FileBeingUploadedLocker $file_being_uploaded_locker,
        FileUploadFinisher $file_upload_finisher,
        FileUploadCanceler $file_upload_canceler
    ) {
        $this->file_being_uploaded_information_provider = $file_being_uploaded_information_provider;
        $this->file_being_uploaded_writer           = $file_being_uploaded_writer;
        $this->file_being_uploaded_locker           = $file_being_uploaded_locker;
        $this->file_upload_finisher                 = $file_upload_finisher;
        $this->file_upload_canceler                 = $file_upload_canceler;
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
