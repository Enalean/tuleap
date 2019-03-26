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
 */

namespace Tuleap\Widget\Event;

use Tuleap\Event\Dispatchable;
use Tuleap\Timetracking\REST\v1\TimetrackingRepresentation;

class UserTimeRetriever implements Dispatchable
{
    const NAME            = 'userTimeRetriever';
    const MAX_TIMES_BATCH = 100;
    const DEFAULT_OFFSET  = 0;

    /**
     * @var int
     */
    private $user_id;

    /**
     * @var String
     */
    private $query;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var TimetrackingRepresentation[]
     */
    private $times;

    /**
     * UserTimeRetriever constructor.
     * @param int $user_id
     * @param String $query
     * @param int $limit
     * @param int $offset
     */
    public function __construct($user_id, $query, $limit = self::MAX_TIMES_BATCH, $offset = self::DEFAULT_OFFSET)
    {
        $this->user_id = $user_id;
        $this->query   = $query;
        $this->limit   = $limit;
        $this->offset  = $offset;
        $this->times  = [];
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return String
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return array
     */
    public function getTimes()
    {
        return $this->times;
    }

    /**
     * @param array $times
     */
    public function addTimes(array $times)
    {
        $this->times = $times;
    }
}
