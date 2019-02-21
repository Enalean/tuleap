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

declare(strict_types = 1);

namespace Tuleap\Docman\Upload\Version;

use Tuleap\Docman\Tus\TusFileInformation;
use Tuleap\Docman\Tus\TusFileInformationProvider;
use Tuleap\Docman\Upload\DocumentAlreadyUploadedInformation;
use Tuleap\Docman\Upload\FileBeingUploadedInformation;

class VersionBeingUploadedInformationProvider implements TusFileInformationProvider
{

    /**
     * @var DocumentOnGoingVersionToUploadDAO
     */
    private $dao;
    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var VersionUploadPathAllocator
     */
    private $path_allocator;

    public function __construct(
        DocumentOnGoingVersionToUploadDAO $dao,
        \Docman_ItemFactory $item_factory,
        VersionUploadPathAllocator $path_allocator
    ) {

        $this->dao            = $dao;
        $this->item_factory   = $item_factory;
        $this->path_allocator = $path_allocator;
    }

    public function getFileInformation(\Psr\Http\Message\ServerRequestInterface $request): ?TusFileInformation
    {
        $version_id = $request->getAttribute('id');
        $user_id    = $request->getAttribute('user_id');

        if ($version_id === null || $user_id === null) {
            return null;
        }

        $document_row = $this->dao->searchDocumentVersionOngoingUploadByVersionIdAndExpirationDate(
            (int) $version_id,
            (new \DateTimeImmutable())->getTimestamp()
        );

        if (empty($document_row)) {
            return null;
        }

        $existing_item = $this->item_factory->getItemFromDb($document_row['item_id']);
        if ($existing_item !== null) {
            return new DocumentAlreadyUploadedInformation((int)$version_id, $document_row['filesize']);
        }
        $allocated_path = $this->path_allocator->getPathForItemBeingUploaded($version_id);

        $current_file_size = file_exists($allocated_path) ? filesize($allocated_path) : 0;
        return new FileBeingUploadedInformation((int)$version_id, $document_row['filesize'], $current_file_size);
    }
}
