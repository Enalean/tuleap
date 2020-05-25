<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Document\DownloadFolderAsZip;

use ZipStream\ZipStream;

final class ErrorsListingBuilder
{
    private const ERROR_FILE_NAME = 'TULEAP_ERRORS.txt';

    /** @var string[] */
    private $bad_file_paths = [];

    public function addBadFilePath(string $bad_file_path): void
    {
        $this->bad_file_paths[] = $bad_file_path;
    }

    public function addErrorsFileIfAnyToArchive(ZipStream $zip): void
    {
        if (count($this->bad_file_paths) === 0) {
            return;
        }
        $contents = dgettext('tuleap-document', 'There was a problem and the following files could not be downloaded:');
        $contents .= PHP_EOL . implode(PHP_EOL, $this->bad_file_paths);
        $zip->addFile(self::ERROR_FILE_NAME, $contents);
    }

    public function hasAnyError(): bool
    {
        return count($this->bad_file_paths) > 0;
    }
}
