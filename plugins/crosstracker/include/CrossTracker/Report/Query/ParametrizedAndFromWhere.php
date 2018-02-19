<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query;

use Tuleap\Tracker\Report\Query\AndFromWhere;

class ParametrizedAndFromWhere implements IProvideParametrizedFromAndWhereSQLFragments
{
    /**
     * @var AndFromWhere
     */
    private $from_where;
    /**
     * @var IProvideParametrizedFromAndWhereSQLFragments
     */
    private $left;
    /**
     * @var IProvideParametrizedFromAndWhereSQLFragments
     */
    private $right;

    public function __construct(
        IProvideParametrizedFromAndWhereSQLFragments $left,
        IProvideParametrizedFromAndWhereSQLFragments $right
    ) {
        $this->left       = $left;
        $this->right      = $right;
        $this->from_where = new AndFromWhere($left, $right);
    }

    /**
     * @return string[]
     */
    public function getFromAsArray()
    {
        return $this->from_where->getFromAsArray();
    }

    /**
     * @return string
     */
    public function getFromAsString()
    {
        return $this->from_where->getFromAsString();
    }

    /**
     * @return string
     */
    public function getWhere()
    {
        return $this->from_where->getWhere();
    }

    /**
     * @return array
     */
    public function getFromParameters()
    {
        return array_merge($this->left->getFromParameters(), $this->right->getFromParameters());
    }

    /**
     * @return array
     */
    public function getWhereParameters()
    {
        return array_merge($this->left->getWhereParameters(), $this->right->getWhereParameters());
    }
}
