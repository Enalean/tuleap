<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\OnTop\Config;

/**
 * Wrapper of array of columns
 * @template-implements \ArrayAccess<int, \Cardwall_Column>
 * @template-implements \IteratorAggregate<int, \Cardwall_Column>
 */
final class ColumnCollection implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @param array<\Cardwall_Column> $columns
     */
    public function __construct(private array $columns = [])
    {
    }

    /**
     * @see \ArrayAccess
     */
    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->columns[] = $value;
        } else {
            $this->columns[$offset] = $value;
        }
    }

    /**
     * @see \ArrayAccess
     */
    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->columns[$offset]);
    }

    /**
     * @see \ArrayAccess
     */
    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->columns[$offset]);
    }

    /**
     * @see \ArrayAccess
     */
    #[\Override]
    public function offsetGet(mixed $offset): ?\Cardwall_Column
    {
        return $this->columns[$offset] ?? null;
    }

    /**
     * @see \IteratorAggregate
     */
    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->columns);
    }

    /**
     * @see \Countable
     */
    #[\Override]
    public function count(): int
    {
        return count($this->columns);
    }

    public function getColumnById(int $id): ?\Cardwall_Column
    {
        foreach ($this->columns as $column) {
            if ($column->id == $id) {
                return $column;
            }
        }
        return null;
    }

    public function getColumnByLabel(string $label): ?\Cardwall_Column
    {
        foreach ($this->columns as $column) {
            if ($column->label == $label) {
                return $column;
            }
        }
        return null;
    }

    /** @return list<\AgileDashboard_ColumnRepresentation> */
    public function getRestValue(): array
    {
        $column_representations = [];
        foreach ($this->columns as $column) {
            $column_representation = new \AgileDashboard_ColumnRepresentation();
            $column_representation->build($column);
            $column_representations[] = $column_representation;
        }
        return $column_representations;
    }
}
