<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

use DateTimeImmutable;
use PFUser;
use Tuleap\Artidoc\Domain\Document\Artidoc;
use Tuleap\Artidoc\Upload\Section\File\ArtidocUploadPathAllocator;
use Tuleap\Artidoc\Upload\Section\File\CreateFileToUpload;
use Tuleap\Artidoc\Upload\Section\File\FileToUpload;
use Tuleap\Artidoc\Upload\Section\File\FinishEmptyFileToUpload;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class FileCreator
{
    public function __construct(
        private CreateFileToUpload $file_to_upload_creator,
        private FinishEmptyFileToUpload $empty_file_to_upload_finisher,
    ) {
    }

    /**
     * @return Ok<CreatedFileRepresentation>|Err<Fault>
     */
    public function create(
        Artidoc $artidoc,
        PFUser $user,
        FilePOSTRepresentation $file_post_representation,
        DateTimeImmutable $current_time,
    ): Ok|Err {
        return $this->file_to_upload_creator->create(
            $artidoc,
            $user,
            $current_time,
            $file_post_representation->name,
            $file_post_representation->file_size,
        )->andThen(function (FileToUpload $file_to_upload) use ($file_post_representation, $artidoc) {
            if ($file_post_representation->file_size === 0) {
                return $this->empty_file_to_upload_finisher->createEmptyFile(
                    $file_to_upload,
                    ArtidocUploadPathAllocator::fromArtidoc($artidoc),
                );
            }

            return Result::ok($file_to_upload);
        })->map(function (FileToUpload $file_to_upload) use ($file_post_representation) {
            return new CreatedFileRepresentation($file_to_upload, $file_post_representation->file_size);
        });
    }
}
