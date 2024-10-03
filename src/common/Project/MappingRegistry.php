<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Project;

final class MappingRegistry
{
    /**
     * @var array<string, array|\ArrayObject>
     */
    private array $custom_mappings;

    /**
     * @param array<int, int> $ugroup_mapping
     */
    public function __construct(private array $ugroup_mapping)
    {
    }

    /**
     * @return array<int, int>
     */
    public function getUgroupMapping(): array
    {
        return $this->ugroup_mapping;
    }

    /**
     * @param array|\ArrayObject $mapping
     */
    public function setCustomMapping(string $key, $mapping): void
    {
        $this->custom_mappings[$key] = $mapping;
    }

    public function hasCustomMapping(string $key): bool
    {
        return isset($this->custom_mappings[$key]);
    }

    /**
     * @return array|\ArrayObject
     */
    public function getCustomMapping(string $key)
    {
        if (! $this->hasCustomMapping($key)) {
            throw new \RuntimeException('Unable to find mapping ' . $key);
        }

        return $this->custom_mappings[$key];
    }
}
