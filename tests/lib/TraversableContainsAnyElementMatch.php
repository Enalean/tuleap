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

use PHPUnit\Framework\Constraint\Constraint;

/**
 * Custom PHPUnit constraint which check that a traversable object contains any element which match another constraint.
 */
class TraversableContainsAnyElementMatch extends Constraint
{
    /**
     * @var Constraint
     */
    private $constraint;

    public function __construct(Constraint $constraint)
    {
        parent::__construct();
        $this->constraint = $constraint;
    }

    /**
     * Returns a string representation of the constraint.
     */
    public function toString(): string
    {
        return 'contains any element which ' . $this->constraint->toString();
    }

    /**
     * Evaluates the constraint for parameter $other. Returns TRUE if the
     * constraint is met, FALSE otherwise.
     *
     * @param mixed $other value or object to evaluate
     */
    protected function matches($other): bool
    {
        foreach ($other as $element) {
            if ($this->constraint->evaluate($element, '', true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $other evaluated value or object
     */
    protected function failureDescription($other): string
    {
        return \sprintf(
            '%s %s',
            \is_array($other) ? 'array' : 'traversable',
            $this->toString()
        );
    }
}
