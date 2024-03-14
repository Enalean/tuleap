<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Files;

use Tuleap\Docman\Upload\Document\DocumentToUpload;
use Tuleap\Docman\Upload\Document\DocumentUploadFinisher;
use Tuleap\Tus\CannotWriteFileException;
use Tuleap\Upload\FileAlreadyUploadedInformation;
use Tuleap\Upload\UploadPathAllocator;

class EmptyFileToUploadFinisher
{
    /**
     * @var DocumentUploadFinisher
     */
    private $finisher;
    /**
     * @var UploadPathAllocator
     */
    private $document_upload_path_allocator;

    public function __construct(
        DocumentUploadFinisher $finisher,
        UploadPathAllocator $document_upload_path_allocator,
    ) {
        $this->finisher                       = $finisher;
        $this->document_upload_path_allocator = $document_upload_path_allocator;
    }

    public function createEmptyFile(DocumentToUpload $document_to_upload, string $filename)
    {
        $item_id          = $document_to_upload->getItemId();
        $file_information = new FileAlreadyUploadedInformation($item_id, $filename, 0);

        $uploaded_document_path = $this->document_upload_path_allocator->getPathForItemBeingUploaded($file_information);

        $allocated_path_directory = dirname($uploaded_document_path);
        if (
            ! \is_dir($allocated_path_directory) &&
            ! \mkdir($allocated_path_directory, 0777, true) && ! \is_dir($allocated_path_directory)
        ) {
            throw new CannotWriteFileException();
        }
        touch($uploaded_document_path);

        $this->finisher->finishUploadEmptyFile($file_information);
    }
}
