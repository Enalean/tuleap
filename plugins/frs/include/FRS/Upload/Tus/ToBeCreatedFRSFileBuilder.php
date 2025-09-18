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

namespace Tuleap\FRS\Upload\Tus;

use FRSFile;
use FRSRelease;

class ToBeCreatedFRSFileBuilder
{
    public const int DEFAULT_PROCESSOR_ID = 100;
    public const int DEFAULT_TYPE_ID      = 100;

    public function buildFRSFile(FRSRelease $release, string $filename, int $filesize, int $user_id): FRSFile
    {
        $now = (new \DateTimeImmutable())->getTimestamp();

        $new_file = new FRSFile();
        $new_file->setRelease($release);
        $new_file->setFileName($filename);
        $new_file->setProcessorID(self::DEFAULT_PROCESSOR_ID);
        $new_file->setTypeID(self::DEFAULT_TYPE_ID);
        $new_file->setUserID($user_id);
        $new_file->setFileSize($filesize);
        $new_file->setStatus('A');
        $new_file->setPostDate($now);
        $new_file->setReleaseTime($now);

        return $new_file;
    }
}
