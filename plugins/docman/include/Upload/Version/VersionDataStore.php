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

namespace Tuleap\Docman\Upload\Version;

use Tuleap\Tus\TusDataStore;
use Tuleap\Tus\TusFileInformationProvider;
use Tuleap\Tus\TusFinisherDataStore;
use Tuleap\Tus\TusLocker;
use Tuleap\Tus\TusTerminaterDataStore;
use Tuleap\Tus\TusWriter;
use Tuleap\Upload\FileBeingUploadedLocker;
use Tuleap\Upload\FileBeingUploadedWriter;

final class VersionDataStore implements TusDataStore
{
    /**
     * @var \Tuleap\Upload\FileBeingUploadedWriter
     */
    private $version_being_uploaded_writer;
    /**
     * @var VersionBeingUploadedInformationProvider
     */
    private $version_being_uploaded_information_provider;
    /**
     * @var VersionUploadFinisher
     */
    private $version_upload_finisher;
    /**
     * @var VersionUploadCanceler
     */
    private $version_upload_canceler;
    /**
     * @var FileBeingUploadedLocker
     */
    private $version_being_uploaded_locker;

    public function __construct(
        VersionBeingUploadedInformationProvider $version_being_uploaded_information_provider,
        FileBeingUploadedWriter $version_being_uploaded_writer,
        VersionUploadFinisher $version_upload_finisher,
        VersionUploadCanceler $version_upload_canceler,
        FileBeingUploadedLocker $version_being_uploaded_locker
    ) {
        $this->version_being_uploaded_information_provider = $version_being_uploaded_information_provider;
        $this->version_being_uploaded_writer               = $version_being_uploaded_writer;
        $this->version_upload_finisher                     = $version_upload_finisher;
        $this->version_upload_canceler                     = $version_upload_canceler;
        $this->version_being_uploaded_locker               = $version_being_uploaded_locker;
    }

    public function getFileInformationProvider(): TusFileInformationProvider
    {
        return $this->version_being_uploaded_information_provider;
    }

    public function getWriter(): TusWriter
    {
        return $this->version_being_uploaded_writer;
    }

    public function getFinisher(): ?TusFinisherDataStore
    {
        return $this->version_upload_finisher;
    }

    public function getTerminater(): ?TusTerminaterDataStore
    {
        return $this->version_upload_canceler;
    }

    public function getLocker(): ?TusLocker
    {
        return $this->version_being_uploaded_locker;
    }
}
