<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

/**
 * @psalm-immutable
 */
final class FilePOSTRepresentation
{
    /**
     * @var int The id of the document {@from body} {@required true}
     */
    public int $artidoc_id;

    /**
     * @var string The file name {@from body} {@required true}
     */
    public string $name;

    /**
     * @var int The file size {@from body} {@required true}
     */
    public int $file_size;

    /**
     * @var string The file type {@from body} {@required true}
     */
    public string $file_type;

    public function __construct(
        int $artidoc_id,
        string $file_name,
        int $file_size,
        string $file_type,
    ) {
        $this->artidoc_id = $artidoc_id;
        $this->name       = $file_name;
        $this->file_size  = $file_size;
        $this->file_type  = $file_type;
    }
}
