<?php
/**
 * Copyright Enalean (c) 2018-Present. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registered trademarks owned by
 *  Enalean SAS. All other trademarks or names are properties of their respective
 *  owners.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\REST\Event;

use Tuleap\Event\Dispatchable;

class GetAdditionalCriteria implements Dispatchable
{
    public const string NAME = 'getAdditionalCriteria';

    /**
     * @var string[]
     */
    private $criteria;

    public function __construct()
    {
        $this->criteria = [];
    }

    /**
     * @return String[]
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * @param String $possible_criterion
     * @param String $expected_value
     */
    public function addCriteria($possible_criterion, $expected_value)
    {
        $this->criteria[$possible_criterion] = $expected_value;
    }
}
