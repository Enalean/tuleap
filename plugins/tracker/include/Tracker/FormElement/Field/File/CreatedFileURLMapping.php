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

namespace Tuleap\Tracker\FormElement\Field\File;

class CreatedFileURLMapping
{
    /**
     * @var array<string, string>
     */
    private $mapping = [];

    public function add(string $previous_file_url, string $new_file_url): void
    {
        $this->mapping[$previous_file_url] = $new_file_url;
    }

    public function get(string $previous_file_url): ?string
    {
        return $this->mapping[$previous_file_url] ?? null;
    }

    public function isEmpty(): bool
    {
        return empty($this->mapping);
    }
}
