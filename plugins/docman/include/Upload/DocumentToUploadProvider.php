<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\Upload;

use Tuleap\Docman\Tus\TusFileProvider;

final class DocumentToUploadProvider implements TusFileProvider
{
    /**
     * @var DocumentUploadPathAllocator
     */
    private $path_allocator;
    /**
     * @var DocumentOngoingUploadDAO
     */
    private $dao;
    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;

    public function __construct(
        DocumentUploadPathAllocator $path_allocator,
        DocumentOngoingUploadDAO $dao,
        \Docman_ItemFactory $item_factory
    ) {
        $this->path_allocator = $path_allocator;
        $this->dao            = $dao;
        $this->item_factory   = $item_factory;
    }

    public function getFile(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $item_id = $request->getAttribute('item_id');
        $user_id = $request->getAttribute('user_id');

        if ($item_id === null || $user_id === null) {
            return null;
        }

        $document_row = $this->dao->searchDocumentOngoingUploadByItemIDUserIDAndExpirationDate(
            $item_id,
            $user_id,
            (new \DateTimeImmutable)->getTimestamp()
        );
        if (empty($document_row)) {
            return null;
        }
        $existing_item = $this->item_factory->getItemFromDb($item_id);
        if ($existing_item !== null) {
            return new DocumentAlreadyUploaded($document_row['filesize']);
        }

        $allocated_path           = $this->path_allocator->getPathForItemBeingUploaded($item_id);
        $allocated_path_directory = dirname($allocated_path);

        if (! \is_dir($allocated_path_directory) &&
            ! \mkdir($allocated_path_directory, 0777, true) && ! \is_dir($allocated_path_directory)) {
            return null;
        }

        if (! file_exists($allocated_path)) {
            touch($allocated_path);
        }
        $handle = fopen($allocated_path, 'ab');

        return new DocumentToUpload($handle, $document_row['filesize'], filesize($allocated_path));
    }
}
