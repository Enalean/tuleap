<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

final class Tracker_Report_ResultJoinerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItRemovesEntriesWhoseKeysAreNotPresentInOtherResult(): void
    {
        $matching_ids = array(123 => 'whatever', 456 => 'whatever', 789 => 'whatever');
        $other_result = array(456 => 'whatever');

        $john = new Tracker_Report_ResultJoiner();
        $results = $john->joinResults($matching_ids, array($other_result));

        $expected = array(456 => 'whatever');
        $this->assertEquals($expected, $results);
    }

    public function testItDoesAnIntersectionWithEveryResults(): void
    {
        $matching_ids = array(123 => 'whatever', 456 => 'whatever', 789 => 'whatever');
        $other_result_1 = array(456 => 'whatever', 789 => 'whatever');
        $other_result_2 = array(456 => 'whatever');

        $john = new Tracker_Report_ResultJoiner();
        $results = $john->joinResults($matching_ids, array($other_result_1, $other_result_2));

        $expected = array(456 => 'whatever');
        $this->assertEquals($expected, $results);
    }
}
