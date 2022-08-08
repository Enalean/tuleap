<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Git\REST\v1;

/**
 * @psalm-immutable
 */
final class GitTreeRepresentation
{
    public string $id;
    public string $name;
    public string $path;
    public string $type;
    public string $mode;


    public function __construct(string $mode, string $type, string $name, string $path, string $id)
    {
        $this->mode = $mode;
        $this->type = $type;
        $this->name = $name;
        $this->path = $path;
        $this->id   = $id;
    }
}
