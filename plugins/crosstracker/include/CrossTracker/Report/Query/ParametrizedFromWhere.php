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

use Tuleap\Tracker\Report\Query\FromWhere;

class ParametrizedFromWhere implements IProvideParametrizedFromAndWhereSQLFragments
{
    /** @var FromWhere */
    private $from_where;

    /** @var array */
    private $from_parameters;

    /** @var array */
    private $where_parameters;

    public function __construct($from, $where, array $from_parameters, array $where_parameters)
    {
        $this->from_where       = new FromWhere($from, $where);
        $this->from_parameters  = $from_parameters;
        $this->where_parameters = $where_parameters;
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
        return $this->from_parameters;
    }

    /**
     * @return array
     */
    public function getWhereParameters()
    {
        return $this->where_parameters;
    }
}
