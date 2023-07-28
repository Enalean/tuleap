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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Reference;

use Generator;

class CrossReferenceByNatureCollection
{
    /**
     * @var CrossReferenceCollection[]
     */
    private $natures = [];

    /**
     * @psalm-param array<string, array{both?: CrossReference[], target?: CrossReference[], source?: CrossReference[]}> $cross_reference_by_nature
     */
    public function __construct(array $cross_reference_by_nature, NatureCollection $available_nature_collection)
    {
        foreach ($cross_reference_by_nature as $nature => $cross_reference_by_key) {
            $available_nature = $available_nature_collection->getNatureFromIdentifier($nature);
            if (! $available_nature) {
                continue;
            }

            $this->natures[$nature] = new CrossReferenceCollection(
                $nature,
                $available_nature->label,
                $cross_reference_by_key['both'] ?? [],
                $cross_reference_by_key['target'] ?? [],
                $cross_reference_by_key['source'] ?? [],
                $available_nature->icon
            );
        }
    }

    public function getByNature(string $key): ?CrossReferenceCollection
    {
        return $this->natures[$key] ?? null;
    }

    /**
     * @return Generator|CrossReferenceCollection[]
     */
    public function getAll()
    {
        foreach ($this->natures as $nature => $collection) {
            yield $collection;
        }
    }
}
