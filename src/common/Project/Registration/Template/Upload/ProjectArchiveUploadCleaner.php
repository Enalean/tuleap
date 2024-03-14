<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template\Upload;

use Tuleap\Upload\UploadPathAllocator;

final readonly class ProjectArchiveUploadCleaner
{
    public function __construct(private UploadPathAllocator $path_allocator, private SearchFileUploadIds $dao, private DeleteUnusedFiles $delete_dao)
    {
    }

    public function deleteUploadedDanglingProjectArchive(\DateTimeImmutable $current_time): void
    {
        $this->delete_dao->deleteUnusableFile($current_time);

        $project_archive_uploaded_item_ids   = array_flip($this->dao->searchFileOngoingUploadIds());
        $project_archive_uploaded_filesystem = $this->path_allocator->getCurrentlyUsedAllocatedPathsPerExpectedItemIDs();
        foreach ($project_archive_uploaded_filesystem as $expected_item_id => $path) {
            if (! isset($project_archive_uploaded_item_ids[(int) $expected_item_id])) {
                unlink($path);
            }
        }
    }
}
