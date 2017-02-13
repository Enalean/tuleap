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

class InComparison implements Term, Visitable, Comparison
{
    /** @var string */
    private $field;

    /** @var InValueWrapper */
    private $value_wrapper;

    public function __construct($field, InValueWrapper $value_wrapper)
    {
        $this->field         = $field;
        $this->value_wrapper = $value_wrapper;
    }

    public function accept(Visitor $visitor, VisitorParameters $parameters)
    {
        return $visitor->visitInComparison($this, $parameters);
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return InValueWrapper
     */
    public function getValueWrapper()
    {
        return $this->value_wrapper;
    }
}
