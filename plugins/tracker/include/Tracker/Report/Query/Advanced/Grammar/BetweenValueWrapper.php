<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
    public function __construct(private readonly ValueWrapper $min_value_wrapper, private readonly ValueWrapper $max_value_wrapper)
    {
    }

    public function accept(ValueWrapperVisitor $visitor, $parameters)
    {
        return $visitor->visitBetweenValueWrapper($this, $parameters);
    }

    public function getMinValue(): ValueWrapper
    {
        return $this->min_value_wrapper;
    }

    public function getMaxValue(): ValueWrapper
    {
        return $this->max_value_wrapper;
    }
}
