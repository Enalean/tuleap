<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\Semantic;

use ArrayAccess;
use Iterator;

class TrackerSemanticCollection implements ArrayAccess, Iterator
{
    /**
     * @var TrackerSemantic[]
     */
    private $semantics_by_name;
    /**
     * @var TrackerSemantic[]
     */
    private $semantics;

    public function __construct()
    {
        $this->semantics         = [];
        $this->semantics_by_name = [];
    }

    public function add(TrackerSemantic $semantic)
    {
        $this->semantics[] = $semantic;

        $this->semantics_by_name[$semantic->getShortName()] = $semantic;
    }

    public function insertAfter($semantic_shortname, TrackerSemantic $semantic)
    {
        $position = 0;
        foreach ($this->semantics as $index => $previous_semantic) {
            if ($previous_semantic->getShortName() === $semantic_shortname) {
                $position = $index + 1;
                break;
            }
        }

        array_splice($this->semantics, $position, 0, [$semantic]);
        $this->semantics_by_name[$semantic->getShortName()] = $semantic;
    }

    #[\Override]
    public function offsetExists($offset): bool
    {
        return isset($this->semantics_by_name[$offset]);
    }

    #[\Override]
    public function offsetGet($offset): ?TrackerSemantic
    {
        return isset($this->semantics_by_name[$offset]) ? $this->semantics_by_name[$offset] : null;
    }

    #[\Override]
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset) || ! isset($this->semantics_by_name[$offset])) {
            $this->add($value);
        } else {
            $this->semantics_by_name[$offset] = $value;
        }
    }

    #[\Override]
    public function offsetUnset($offset): void
    {
        unset($this->semantics_by_name[$offset]);

        foreach ($this->semantics as $index => $semantic) {
            if ($semantic->getShortName() === $offset) {
                unset($this->semantics[$index]);
                break;
            }
        }
    }

    #[\Override]
    public function current(): TrackerSemantic|false
    {
        return current($this->semantics);
    }

    #[\Override]
    public function next(): void
    {
        next($this->semantics);
    }

    #[\Override]
    public function key(): ?int
    {
        return key($this->semantics);
    }

    #[\Override]
    public function valid(): bool
    {
        return key($this->semantics) !== null;
    }

    #[\Override]
    public function rewind(): void
    {
        reset($this->semantics);
    }
}
