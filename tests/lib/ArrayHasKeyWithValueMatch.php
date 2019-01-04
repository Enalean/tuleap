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

use PHPUnit\Framework\Constraint\Composite;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Custom PHPUnit constraint which check that an array has a key which value satisfies another constraint.
 */
class ArrayHasKeyWithValueMatch extends Composite
{

    protected $key;

    public function __construct(Constraint $constraint, $key)
    {
        parent::__construct($constraint);
        $this->key = $key;
    }

    public function toString(): string
    {
        return 'the value of key \'' . $this->key . '\' ' . parent::innerConstraint()->toString();
    }

    /**
     * Evaluates the constraint for parameter $other. Returns TRUE if the
     * constraint is met, FALSE otherwise.
     *
     * @param mixed $other Value or object to evaluate.
     * @return bool
     */
    public function evaluate($other, $description = '', $returnResult = false)
    {
        return parent::evaluate(
            $other[$this->key],
            $description,
            $returnResult
        );
    }
}
