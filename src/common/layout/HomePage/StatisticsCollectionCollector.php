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

namespace Tuleap\layout\HomePage;

use Tuleap\Event\Dispatchable;

class StatisticsCollectionCollector implements Dispatchable
{
    const NAME = 'statisticsCollectionCollector';
    /**
     * @var StatisticsCollection
     */
    private $collection;
    /**
     * @var int
     */
    private $timestamp;

    public function __construct(StatisticsCollection $collection, $timestamp)
    {
        $this->collection = $collection;
        $this->timestamp  = $timestamp;
    }

    public function addStatistics($label, $total, $last_month_growth)
    {
        if ($total > 0) {
            $this->collection->addStatistic($label, $total, $last_month_growth);
        }
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
