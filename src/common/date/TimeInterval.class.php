<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * A time interval.
 */
class TimeInterval
{

    /**
     * Creates a new time interval from UNIX timestamps.
     *
     * @param int $start
     * @param int $end
     * @return \ITimeInterval
     */
    public static function fromUnixTimestamps($start, $end)
    {
        return new TimeInterval($start, $end);
    }

    /**
     * @var int
     */
    private $start;

    /**
     * @var int
     */
    private $end;

    /**
     * @return int
     */
    public function getStartTimestamp()
    {
        return $this->start;
    }

    /**
     * @return int
     */
    public function getEndTimestamp()
    {
        return $this->end;
    }

    public function __construct($start, $end)
    {
        $this->assertTimestampIsPositive($start, 'Start');
        $this->assertTimestampIsPositive($end, 'End');
        $this->assertStartIsStrictlyBeforeEnd($start, $end);

        $this->start = $start;
        $this->end   = $end;
    }

    private function assertTimestampIsPositive($timestamp, $name)
    {
        if ($timestamp <= 0) {
            throw new Exception("$name timestamp must be a positive number");
        }
    }

    private function assertStartIsStrictlyBeforeEnd($start, $end)
    {
        if ($start >= $end) {
            throw new Exception('Start must be strictly before end');
        }
    }
}
