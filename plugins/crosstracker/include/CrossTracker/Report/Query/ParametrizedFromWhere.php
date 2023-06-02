<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

final class ParametrizedFromWhere implements IProvideParametrizedFromAndWhereSQLFragments
{
    /** @var ParametrizedFrom */
    private $parametrized_from;

    /** @var array */
    private $where_parameters;

    /** @var string */
    private $where;

    public function __construct($from, $where, array $from_parameters, array $where_parameters)
    {
        $this->parametrized_from = new ParametrizedFrom($from, $from_parameters);
        $this->where             = $where;
        $this->where_parameters  = $where_parameters;
    }

    /**
     * @return string
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * @return ParametrizedFrom[]
     */
    public function getAllParametrizedFrom()
    {
        return [$this->parametrized_from];
    }

    /**
     * @return array
     */
    public function getWhereParameters()
    {
        return $this->where_parameters;
    }
}
