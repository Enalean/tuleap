<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template\Upload;


final readonly class UploadedArchiveForProjectArchiver implements ArchiveUploadedArchive
{
    public function __construct(private string $data_dir)
    {
    }

    public function archive(\Project $project, string $uploaded_archive_path): string
    {
        $destination = $this->data_dir . '/project/' . $project->getID() . '/created-from-archive/uploaded-archive-for-' . $project->getID() . '.zip';

        \Psl\Filesystem\copy(
            $uploaded_archive_path,
            $destination,
        );

        return $destination;
    }
}
