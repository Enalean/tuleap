<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Layout\BreadCrumbDropdown;

final class BreadCrumbLink
{
    /**
     * @var array<string, string>
     */
    private array $data_attributes = [];
    private string $project_icon   = '';

    public function __construct(
        private readonly string $label,
        private readonly string $url,
    ) {
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setDataAttribute(string $name, string $value): void
    {
        $this->data_attributes[$name] = $value;
    }

    /**
     * @return array<string, string>
     */
    public function getDataAttributes(): array
    {
        return $this->data_attributes;
    }

    public function setProjectIcon(string $project_icon): void
    {
        $this->project_icon = $project_icon;
    }

    public function getProjectIcon(): string
    {
        return $this->project_icon;
    }
}
