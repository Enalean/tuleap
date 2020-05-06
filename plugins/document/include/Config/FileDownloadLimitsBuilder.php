<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Config;

class FileDownloadLimitsBuilder
{
    public function build(): FileDownloadLimits
    {
        $max_archive_size = (int) \ForgeConfig::get(FileDownloadLimits::MAX_ARCHIVE_SIZE_NAME);
        if ($max_archive_size < 1) {
            $max_archive_size = FileDownloadLimits::MAX_ARCHIVE_SIZE_DEFAULT_IN_MB;
        }

        $warning_thresold = (int) \ForgeConfig::get(FileDownloadLimits::WARNING_THRESHOLD_NAME);
        if ($warning_thresold < 1) {
            $warning_thresold = FileDownloadLimits::WARNING_THRESHOLD_DEFAULT_IN_MB;
        }

        return new FileDownloadLimits($max_archive_size, $warning_thresold);
    }
}
