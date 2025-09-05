<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Upload\NextGen;

use Tuleap\DB\DBConnection;
use Tuleap\Tus\CannotWriteFileException;
use Tuleap\Tus\NextGen\TusFileInformation;
use Tuleap\Tus\NextGen\TusWriter;

final readonly class FileBeingUploadedWriter implements TusWriter
{
    public function __construct(private PathAllocator $path_allocator, private DBConnection $db_connection)
    {
    }

    #[\Override]
    public function writeChunk(TusFileInformation $file_information, int $offset, $input_source): int
    {
        $allocated_path = $this->path_allocator->getPathForItemBeingUploaded($file_information);

        $allocated_path_directory = dirname($allocated_path);
        if (
            ! \is_dir($allocated_path_directory) &&
            ! \mkdir($allocated_path_directory, 0777, true) && ! \is_dir($allocated_path_directory)
        ) {
            throw new CannotWriteFileException();
        }

        $file_stream = \fopen($allocated_path, 'cb');
        if ($file_stream === false) {
            throw new CannotWriteFileException();
        }
        if (\fseek($file_stream, $offset) === -1) {
            throw new CannotWriteFileException();
        }

        $max_size_to_copy = $file_information->getLength() - $file_information->getOffset();
        $copied_size      = stream_copy_to_stream($input_source, $file_stream, $max_size_to_copy);
        \fclose($file_stream);

        $this->handlePotentialDBReconnection();

        if ($copied_size === false) {
            throw new CannotWriteFileException();
        }

        return $copied_size;
    }

    private function handlePotentialDBReconnection(): void
    {
        // The copy of the file to the disk can be quite long so the DB
        // server can decide to close the connection, we want to make sure
        // the DB connection is still up at the end of the copy to not break
        // the rest of the process
        $this->db_connection->reconnectAfterALongRunningProcess();
    }
}
