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

namespace Tuleap\Artidoc\Upload\Section\File;

use Tuleap\Tus\Identifier\FileIdentifier;

final readonly class FileUploadCleaner
{
    public function __construct(
        private ArtidocUploadPathAllocator $path_allocator,
        private SearchFileOngoingUploadIds $search_ongoing_uploads,
        private DeleteUnusableFiles $deletor,
    ) {
    }

    public function deleteDanglingFilesToUpload(\DateTimeImmutable $current_time): void
    {
        $this->deletor->deleteUnusableFiles($current_time);

        $file_being_uploaded_item_ids = array_reduce(
            $this->search_ongoing_uploads->searchFileOngoingUploadIds(),
            static function (array $acc, FileIdentifier $id) {
                $acc[$id->toString()] = true;

                return $acc;
            },
            [],
        );

        $file_being_uploaded_filesystem = $this->path_allocator->getCurrentlyUsedAllocatedPathsPerExpectedItemIDs();
        foreach ($file_being_uploaded_filesystem as $expected_item_id => $path) {
            if (! isset($file_being_uploaded_item_ids[$expected_item_id]) && \is_file("$path")) {
                \unlink("$path");
            }
        }
    }
}
