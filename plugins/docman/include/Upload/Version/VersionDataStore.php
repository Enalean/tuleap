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

use Tuleap\Docman\Tus\TusDataStore;
use Tuleap\Docman\Tus\TusFileInformationProvider;
use Tuleap\Docman\Tus\TusFinisherDataStore;
use Tuleap\Docman\Tus\TusLocker;
use Tuleap\Docman\Tus\TusTerminaterDataStore;
use Tuleap\Docman\Tus\TusWriter;
use Tuleap\Docman\Upload\FileBeingUploadedWriter;

final class VersionDataStore implements TusDataStore
{
    /**
     * @var FileBeingUploadedWriter
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

    public function __construct(
        VersionBeingUploadedInformationProvider $version_being_uploaded_information_provider,
        FileBeingUploadedWriter $version_being_uploaded_writer,
        VersionUploadFinisher $version_upload_finisher
    ) {

        $this->version_being_uploaded_information_provider = $version_being_uploaded_information_provider;
        $this->version_being_uploaded_writer               = $version_being_uploaded_writer;
        $this->version_upload_finisher                     = $version_upload_finisher;
    }

    public function getFileInformationProvider() : TusFileInformationProvider
    {
        return $this->version_being_uploaded_information_provider;
    }

    public function getWriter() : TusWriter
    {
        return $this->version_being_uploaded_writer;
    }

    public function getFinisher() : ?TusFinisherDataStore
    {
        return $this->version_upload_finisher;
    }

    public function getTerminater(): ?TusTerminaterDataStore
    {
        return null;
    }

    public function getLocker() : ?TusLocker
    {
        return null;
    }
}
