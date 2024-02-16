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

namespace Tuleap\Project\REST\v1\File;
/**
 * @psalm-immutable
 */
final class ProjectFilePOSTRepresentation
{
    /**
     * @var string The filename of the file to upload {@from body} {@required true}
     */
    public string $file_name;
    /**
     * @var int The filesize of the file to upload {@from body} {@required true} {@min 0}
     */
    public int $file_size;

    public function __construct(
        string $file_name,
        int $file_size,
    ) {
        $this->file_name = $file_name;
        $this->file_size = $file_size;
    }
}
