<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Search;

/**
 * @psalm-immutable
 */
final class SearchColumn
{
    private function __construct(private string $name, private string $label, private bool $is_custom_property)
    {
    }

    public static function buildForCustomProperty(string $name, string $label): self
    {
        return new self($name, $label, true);
    }

    public static function buildForHardcodedProperty(string $name, string $label): self
    {
        return new self($name, $label, false);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isCustomProperty(): bool
    {
        return $this->is_custom_property;
    }
}
