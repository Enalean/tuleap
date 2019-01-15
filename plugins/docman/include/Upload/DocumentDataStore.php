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

namespace Tuleap\Docman\Upload;

use Tuleap\Docman\Tus\TusDataStore;
use Tuleap\Docman\Tus\TusFileInformationProvider;
use Tuleap\Docman\Tus\TusFinisherDataStore;
use Tuleap\Docman\Tus\TusLocker;
use Tuleap\Docman\Tus\TusTerminaterDataStore;
use Tuleap\Docman\Tus\TusWriter;

final class DocumentDataStore implements TusDataStore
{
    /**
     * @var DocumentBeingUploadedInformationProvider
     */
    private $document_being_uploaded_information_provider;
    /**
     * @var DocumentBeingUploadedWriter
     */
    private $document_being_uploaded_writer;
    /**
     * @var DocumentBeingUploadedLocker
     */
    private $document_being_uploaded_locker;
    /**
     * @var DocumentUploadFinisher
     */
    private $document_upload_finisher;
    /**
     * @var DocumentUploadCanceler
     */
    private $document_upload_canceler;

    public function __construct(
        DocumentBeingUploadedInformationProvider $document_being_uploaded_information_provider,
        DocumentBeingUploadedWriter $document_being_uploaded_writer,
        DocumentBeingUploadedLocker $document_being_uploaded_locker,
        DocumentUploadFinisher $document_upload_finisher,
        DocumentUploadCanceler $document_upload_canceler
    ) {
        $this->document_being_uploaded_information_provider = $document_being_uploaded_information_provider;
        $this->document_being_uploaded_writer               = $document_being_uploaded_writer;
        $this->document_being_uploaded_locker               = $document_being_uploaded_locker;
        $this->document_upload_finisher                     = $document_upload_finisher;
        $this->document_upload_canceler                     = $document_upload_canceler;
    }

    public function getFileInformationProvider() : TusFileInformationProvider
    {
        return $this->document_being_uploaded_information_provider;
    }

    public function getWriter() : TusWriter
    {
        return $this->document_being_uploaded_writer;
    }

    public function getFinisher() : ?TusFinisherDataStore
    {
        return $this->document_upload_finisher;
    }

    public function getTerminater(): ?TusTerminaterDataStore
    {
        return $this->document_upload_canceler;
    }

    public function getLocker() : ?TusLocker
    {
        return $this->document_being_uploaded_locker;
    }
}
