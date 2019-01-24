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

namespace Tuleap\Docman\REST\v1;

use Tuleap\Docman\Tus\CannotWriteFileException;
use Tuleap\Docman\Upload\DocumentAlreadyUploadedInformation;
use Tuleap\Docman\Upload\DocumentToUpload;
use Tuleap\Docman\Upload\DocumentUploadFinisher;
use Tuleap\Docman\Upload\DocumentUploadPathAllocator;

class EmptyFileToUploadFinisher
{
    /**
     * @var DocumentUploadFinisher
     */
    private $finisher;
    /**
     * @var DocumentUploadPathAllocator
     */
    private $document_upload_path_allocator;

    public function __construct(
        DocumentUploadFinisher $finisher,
        DocumentUploadPathAllocator $document_upload_path_allocator
    ) {
        $this->finisher                       = $finisher;
        $this->document_upload_path_allocator = $document_upload_path_allocator;
    }

    public function createEmptyFile(DocumentToUpload $document_to_upload)
    {
        $item_id = $document_to_upload->getItemId();

        $uploaded_document_path = $this->document_upload_path_allocator->getPathForItemBeingUploaded($item_id);

        $allocated_path_directory = dirname($uploaded_document_path);
        if (! \is_dir($allocated_path_directory) &&
            ! \mkdir($allocated_path_directory, 0777, true) && ! \is_dir($allocated_path_directory)) {
            throw new CannotWriteFileException();
        }
        touch($uploaded_document_path);

        $this->finisher->finishUpload(new DocumentAlreadyUploadedInformation($item_id, 0));
    }
}
