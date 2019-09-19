<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class TimeIntervalTest extends TuleapTestCase
{

    public function itRefusesNegativeTimeStamps()
    {
        $this->assertInvalidIntervalTimestamps(-1, 1);
        $this->assertInvalidIntervalTimestamps(1, -1);
    }

    public function itEnsuresStartIsStrictlyBeforeEnd()
    {
        $this->assertInvalidIntervalTimestamps(2, 1);
        $this->assertInvalidIntervalTimestamps(2, 2);
    }

    public function itHasAStartAndAnEnd()
    {
        $start = 1;
        $end   = 2;

        $interval = TimeInterval::fromUnixTimestamps($start, $end);

        $this->assertEqual($start, $interval->getStartTimestamp());
        $this->assertEqual($end, $interval->getEndTimestamp());
    }

    private function assertInvalidIntervalTimestamps($start, $end)
    {
        try {
            TimeInterval::fromUnixTimestamps($start, $end);
            $this->fail('should have thrown exception');
        } catch (Exception $exc) {
        }
    }
}
