<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\Grammar;

class BetweenValueWrapper implements ValueWrapper
{
    /**
     * @var ValueWrapper
     */
    private $min_value_wrapper;
    /**
     * @var ValueWrapper
     */
    private $max_value_wrapper;

    public function __construct(ValueWrapper $min_value_wrapper, ValueWrapper $max_value_wrapper)
    {
        $this->min_value_wrapper = $min_value_wrapper;
        $this->max_value_wrapper = $max_value_wrapper;
    }

    public function accept(ValueWrapperVisitor $visitor, ValueWrapperParameters $parameters)
    {
        return $visitor->visitBetweenValueWrapper($this, $parameters);
    }

    /**
     * @return ValueWrapper
     */
    public function getMinValue()
    {
        return $this->min_value_wrapper;
    }

    /**
     * @return ValueWrapper
     */
    public function getMaxValue()
    {
        return $this->max_value_wrapper;
    }
}
