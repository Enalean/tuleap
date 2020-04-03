<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\Upload\Document;

use PFUser;
use Tuleap\REST\RESTCurrentUserMiddleware;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Tus\TusFileInformationProvider;
use Tuleap\Upload\FileAlreadyUploadedInformation;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\UploadPathAllocator;

final class DocumentBeingUploadedInformationProvider implements TusFileInformationProvider
{
    /**
     * @var UploadPathAllocator
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
        UploadPathAllocator $path_allocator,
        DocumentOngoingUploadDAO $dao,
        \Docman_ItemFactory $item_factory
    ) {
        $this->path_allocator = $path_allocator;
        $this->dao            = $dao;
        $this->item_factory   = $item_factory;
    }

    public function getFileInformation(\Psr\Http\Message\ServerRequestInterface $request): ?TusFileInformation
    {
        $item_id = $request->getAttribute('id');

        if ($item_id === null) {
            return null;
        }

        $item_id = (int) $item_id;
        $current_user = $request->getAttribute(RESTCurrentUserMiddleware::class);
        \assert($current_user instanceof PFUser);

        $document_row = $this->dao->searchDocumentOngoingUploadByItemIDUserIDAndExpirationDate(
            $item_id,
            (int) $current_user->getId(),
            (new \DateTimeImmutable())->getTimestamp()
        );
        if (empty($document_row)) {
            return null;
        }
        $existing_item = $this->item_factory->getItemFromDb($item_id);
        $length        = (int) $document_row['filesize'];
        if ($existing_item !== null) {
            return new FileAlreadyUploadedInformation($item_id, $document_row['filename'], $length);
        }

        $current_file_size = $this->getCurrentFileSize($item_id, $document_row['filename'], $length);

        return new FileBeingUploadedInformation($item_id, $document_row['filename'], $length, $current_file_size);
    }

    private function getCurrentFileSize(int $item_id, string $name, int $length): int
    {
        $temporary_file_information = new FileBeingUploadedInformation($item_id, $name, $length, 0);
        $allocated_path             = $this->path_allocator->getPathForItemBeingUploaded($temporary_file_information);

        $current_file_size = file_exists($allocated_path) ? filesize($allocated_path) : 0;

        return $current_file_size;
    }
}
