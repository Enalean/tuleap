<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap;

use Closure;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\IsEqual;

/**
 * Collection of custom assertions.
 */
trait CustomAssert
{

    /**
     * Asserts that the array contains at least one element with a given key / value.
     */
    protected function assertContainsAnyArrayWithKey(array $array, $key, $value)
    {
        $this->assertThat(
            $array,
            new TraversableContainsAnyElementMatch(new ArrayHasKeyWithValueMatch(new IsEqual($value), $key))
        );
    }

    /**
     * Asserts that the array contains at least one element which satisfies the given predicate.
     */
    protected function assertContainsAnySatisfies(array $array, Closure $predicate)
    {
        $this->assertThat($array, $this->containsAnySatisfies($predicate));
    }

    /**
     * Asserts that the array does not contains any element which satisfies the given predicate.
     */
    protected function assertNotContainsAnySatisfies(array $array, Closure $predicate)
    {
        $this->assertThat($array, $this->logicalNot($this->containsAnySatisfies($predicate)));
    }

    /**
     * Build new constraint which verifies any element of a traversable satisfies given predicate.
     *
     * @return TraversableContainsAnyElementMatch
     */
    private function containsAnySatisfies(Closure $predicate)
    {
        return new TraversableContainsAnyElementMatch(new Callback($predicate));
    }
}
