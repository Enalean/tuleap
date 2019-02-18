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

use Tuleap\Docman\Tus\TusFileInformation;
use Tuleap\Docman\Tus\TusTerminaterDataStore;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadDAO;

final class DocumentUploadCanceler implements TusTerminaterDataStore
{
    /**
     * @var DocumentUploadPathAllocator
     */
    private $path_allocator;
    /**
     * @var DocumentOngoingUploadDAO
     */
    private $dao;

    public function __construct(DocumentUploadPathAllocator $path_allocator, DocumentOngoingUploadDAO $dao)
    {
        $this->path_allocator = $path_allocator;
        $this->dao            = $dao;
    }

    public function terminateUpload(TusFileInformation $file_information) : void
    {
        $this->dao->deleteByItemID($file_information->getID());
        $file_path = $this->path_allocator->getPathForItemBeingUploaded($file_information->getID());
        if (\is_file($file_path)) {
            @\unlink($file_path);
        }
    }
}
