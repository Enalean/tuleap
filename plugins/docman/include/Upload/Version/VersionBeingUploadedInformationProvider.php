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

namespace Tuleap\Docman\Upload\Version;

use Tuleap\Request\NotFoundException;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Tus\TusFileInformationProvider;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\UploadPathAllocator;
use Tuleap\User\ProvideCurrentRequestUser;

final class VersionBeingUploadedInformationProvider implements TusFileInformationProvider
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
     * @var UploadPathAllocator
     */
    private $path_allocator;

    public function __construct(
        DocumentOnGoingVersionToUploadDAO $dao,
        \Docman_ItemFactory $item_factory,
        UploadPathAllocator $path_allocator,
        private ProvideCurrentRequestUser $current_request_user_provider,
    ) {
        $this->dao            = $dao;
        $this->item_factory   = $item_factory;
        $this->path_allocator = $path_allocator;
    }

    #[\Override]
    public function getFileInformation(\Psr\Http\Message\ServerRequestInterface $request): ?TusFileInformation
    {
        $version_id = $request->getAttribute('id');

        if ($version_id === null) {
            return null;
        }

        $version_id   = (int) $version_id;
        $current_user = $this->current_request_user_provider->getCurrentRequestUser($request);
        if ($current_user === null) {
            throw new NotFoundException();
        }

        $document_row = $this->dao->searchDocumentVersionOngoingUploadByVersionIDUserIDAndExpirationDate(
            $version_id,
            (int) $current_user->getId(),
            (new \DateTimeImmutable())->getTimestamp()
        );

        if (empty($document_row)) {
            return null;
        }

        $item = $this->item_factory->getItemFromDb($document_row['item_id']);
        if (! $item) {
            return null;
        }

        $length            = (int) $document_row['filesize'];
        $filename          = $document_row['filename'];
        $current_file_size = $this->getCurrentFileSize($version_id, $filename, $length);

        return new FileBeingUploadedInformation($version_id, $filename, $length, $current_file_size);
    }

    private function getCurrentFileSize(int $version_id, string $name, int $length): int
    {
        $temporary_file_information = new FileBeingUploadedInformation($version_id, $name, $length, 0);
        $allocated_path             = $this->path_allocator->getPathForItemBeingUploaded($temporary_file_information);

        $current_file_size = file_exists($allocated_path) ? filesize($allocated_path) : 0;

        return $current_file_size;
    }
}
