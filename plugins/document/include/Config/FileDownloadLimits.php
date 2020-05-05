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

class FileDownloadLimits
{
    public const MAX_ARCHIVE_SIZE_NAME           = 'plugin_document_max_archive_size';
    public const WARNING_THRESHOLD_NAME          = 'plugin_document_warning_threshold';
    public const MAX_ARCHIVE_SIZE_DEFAULT_IN_MB  = 2000;
    public const WARNING_THRESHOLD_DEFAULT_IN_MB = 50;

    /**
     * @var int
     */
    private $max_archive_size;
    /**
     * @var int
     */
    private $warning_threshold;

    public function __construct(int $max_archive_size, int $warning_threshold)
    {
        $this->max_archive_size  = $max_archive_size;
        $this->warning_threshold = $warning_threshold;
    }

    public function getMaxArchiveSize(): int
    {
        return $this->max_archive_size;
    }

    public function getWarningThreshold(): int
    {
        return $this->warning_threshold;
    }
}
