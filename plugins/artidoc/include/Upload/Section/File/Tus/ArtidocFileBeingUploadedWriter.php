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

namespace Tuleap\Artidoc\Upload\Section\File\Tus;

use Tuleap\Artidoc\Upload\Section\File\ArtidocUploadPathAllocator;
use Tuleap\Artidoc\Upload\Section\File\SearchUpload;
use Tuleap\Artidoc\Upload\Section\File\UploadFileInformation;
use Tuleap\DB\DBConnection;
use Tuleap\Tus\CannotWriteFileException;
use Tuleap\Tus\NextGen\TusFileInformation;
use Tuleap\Tus\NextGen\TusWriter;
use Tuleap\Upload\NextGen\FileBeingUploadedWriter;

final class ArtidocFileBeingUploadedWriter implements TusWriter
{
    public function __construct(private SearchUpload $search, private DBConnection $db_connection)
    {
    }

    public function writeChunk(TusFileInformation $file_information, int $offset, $input_source): int
    {
        return $this->search
            ->searchUpload($file_information->getID())
            ->match(
                function (UploadFileInformation $file_information) use ($offset, $input_source) {
                    $locker = new FileBeingUploadedWriter(
                        ArtidocUploadPathAllocator::fromFileInformation($file_information),
                        $this->db_connection,
                    );

                    return $locker->writeChunk($file_information, $offset, $input_source);
                },
                static fn () => throw new CannotWriteFileException()
            );
    }
}
