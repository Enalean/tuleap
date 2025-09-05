<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Tests;

/**
 * @psalm-type FreetextSection = array{
 *     type: "freetext",
 *     id: string,
 *     title: string,
 *     description: string,
 *     level: int
 * }
 * @psalm-type ArtifactSection = array{
 *     type: "artifact",
 *     id: string,
 *     title: string,
 *     description: string,
 *     level: int,
 *     artifact: array{ id: int },
 *     fields: list<array>
 * }
 * @template-implements \IteratorAggregate<int, FreetextSection | ArtifactSection>
 * @template-implements \ArrayAccess<int, FreetextSection | ArtifactSection>
 */
final readonly class SectionsRESTCollection implements \IteratorAggregate, \ArrayAccess, \Countable
{
    public function __construct(private int $artidoc_id, private array $sections)
    {
    }

    public function findArtifactSectionUUID(int $artifact_id): string
    {
        $section = array_find(
            $this->sections,
            static fn(array $section) => $artifact_id === ($section['artifact']['id'] ?? 0)
        );
        if ($section === null) {
            throw new \RuntimeException(
                sprintf(
                    'Could not find section for artifact #%s in artidoc #%s',
                    $artifact_id,
                    $this->artidoc_id
                )
            );
        }
        return $section['id'];
    }

    /**
     * @return list<string>
     */
    public function getTitles(): array
    {
        return array_values(
            array_map(
                static fn(array $section): string => $section['title'],
                $this->sections
            )
        );
    }

    /**
     * @return list<int>
     */
    public function getArtifactSectionIds(): array
    {
        $artifact_ids = [];
        foreach ($this->sections as $section) {
            if (isset($section['artifact'])) {
                $artifact_ids[] = $section['artifact']['id'];
            }
        }
        return $artifact_ids;
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->sections);
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->sections);
    }

    #[\Override]
    public function offsetGet(mixed $offset): array
    {
        return $this->sections[$offset];
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \LogicException('SectionsRESTCollection is immutable');
    }

    #[\Override]
    public function offsetUnset(mixed $offset): never
    {
        throw new \LogicException('SectionsRESTCollection is immutable');
    }

    #[\Override]
    public function count(): int
    {
        return count($this->sections);
    }
}
