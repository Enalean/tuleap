<?php
/**
 * Copyright (c) Enalean, 2016-2017. All Rights Reserved.
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

class BetweenComparison implements Term, Visitable, Comparison
{
    /**
     * @var string
     */
    private $field;
    /**
     * @var BetweenValueWrapper
     */
    private $between_value;

    public function __construct($field, BetweenValueWrapper $between_value)
    {
        $this->field        = $field;
        $this->between_value = $between_value;
    }

    public function accept(Visitor $visitor, VisitorParameters $parameters)
    {
        return $visitor->visitBetweenComparison($this, $parameters);
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return BetweenValueWrapper
     */
    public function getValueWrapper()
    {
        return $this->between_value;
    }
}
