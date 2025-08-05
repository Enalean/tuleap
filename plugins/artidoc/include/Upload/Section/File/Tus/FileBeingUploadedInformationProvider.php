<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Artidoc\Upload\Section\File\Tus;

use DateTimeImmutable;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Artidoc\Upload\Section\File\ArtidocUploadPathAllocator;
use Tuleap\Artidoc\Upload\Section\File\RetrieveUploadedFileWithArtidoc;
use Tuleap\Artidoc\Upload\Section\File\SearchNotExpiredOngoingUpload;
use Tuleap\Artidoc\Upload\Section\File\UploadFileInformation;
use Tuleap\Artidoc\Upload\Section\File\UploadedFileWithArtidoc;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\Tus\Identifier\FileIdentifier;
use Tuleap\Tus\Identifier\FileIdentifierFactory;
use Tuleap\Tus\Identifier\InvalidFileIdentifierStringException;
use Tuleap\Tus\NextGen\TusFileInformation;
use Tuleap\Tus\NextGen\TusFileInformationProvider;
use Tuleap\Upload\NextGen\FileBeingUploadedInformation;
use Tuleap\User\ProvideCurrentRequestUser;

final readonly class FileBeingUploadedInformationProvider implements TusFileInformationProvider
{
    public function __construct(
        private RetrieveUploadedFileWithArtidoc $retrieve_uploaded_file_with_artidoc,
        private FileIdentifierFactory $identifier_factory,
        private SearchNotExpiredOngoingUpload $search,
        private ProvideCurrentRequestUser $current_user_request,
    ) {
    }

    public function getFileInformation(ServerRequestInterface $request): ?TusFileInformation
    {
        try {
            $id = $this->identifier_factory->buildFromHexadecimalString((string) $request->getAttribute('id'));
        } catch (InvalidFileIdentifierStringException) {
            return null;
        }

        $current_user = $this->current_user_request->getCurrentRequestUser($request);
        if ($current_user === null) {
            return null;
        }

        return $this->getUploadedFileWithArtidoc($current_user, $id)
            ->match(
                function (UploadedFileWithArtidoc $upload) {
                    return new FileBeingUploadedInformation(
                        $upload->file->getID(),
                        $upload->file->getName(),
                        $upload->file->getLength(),
                        $this->getCurrentFileSize($upload->file)
                    );
                },
                static fn() => null,
            );
    }

    /**
     * @return Ok<UploadedFileWithArtidoc>|Err<Fault>
     */
    private function getUploadedFileWithArtidoc(\PFUser $user, FileIdentifier $id): Ok|Err
    {
        return $this->search
            ->searchNotExpiredOngoingUpload($id, (int) $user->getId(), new DateTimeImmutable())
            ->andThen(
                fn (UploadFileInformation $file) => $this->retrieve_uploaded_file_with_artidoc->getUploadedFileWithArtidoc($user, $file)
            );
    }

    private function getCurrentFileSize(UploadFileInformation $file_information): int
    {
        $path_allocator = ArtidocUploadPathAllocator::fromFileInformation($file_information);

        $path = $path_allocator->getPathForItemBeingUploaded($file_information);

        return file_exists($path) ? filesize($path) : 0;
    }
}
