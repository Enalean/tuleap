<?php
/**
 * Copyright (c) Enalean, 2016-2018. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Tracker\Artifact\Attachment;

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

class PaginatedTemporaryFiles
{

    private $total_count;

    /**
     * @var LegacyDataAccessResultInterface
     */
    private $files;

    public function __construct(LegacyDataAccessResultInterface $files, $total_count)
    {
        $this->files       = $files;
        $this->total_count = $total_count;
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function getTotalCount()
    {
        return $this->total_count;
    }
}
