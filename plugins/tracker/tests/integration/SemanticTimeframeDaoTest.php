<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Timeframe;

use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class SemanticTimeframeDaoTest extends TestIntegrationTestCase
{
    private static int $first_timeframe_using_end_date_tracker_id;
    private static int $second_timeframe_using_end_date_tracker_id;
    private static int $first_timeframe_using_duration_tracker_id;
    private static int $second_timeframe_using_duration_tracker_id;
    private SemanticTimeframeDao $dao;

    protected function setUp(): void
    {
        $this->dao                                        = new SemanticTimeframeDao();
        $db                                               = DBFactory::getMainTuleapDBConnection()->getDB();
        self::$first_timeframe_using_end_date_tracker_id  = (int) $db->insertReturnId(
            'tracker',
            [
                'group_id' => 105,
                'name' => 'End date Timeframe',
                'description' => 'First tracker with timeframe using end date',
                'item_name' => 'end_date_timeframe',
            ]
        );
        self::$second_timeframe_using_end_date_tracker_id = (int) $db->insertReturnId(
            'tracker',
            [
                'group_id' => 105,
                'name' => 'End date Timeframe 2',
                'description' => 'Second tracker with timeframe using end date',
                'item_name' => 'end_date_timeframe_2',
            ]
        );
        self::$first_timeframe_using_duration_tracker_id  = (int) $db->insertReturnId(
            'tracker',
            [
                'group_id' => 105,
                'name' => 'Duration Timeframe',
                'description' => 'Tracker with timeframe using duration',
                'item_name' => 'duration_timeframe',
            ]
        );
        self::$second_timeframe_using_duration_tracker_id = (int) $db->insertReturnId(
            'tracker',
            [
                'group_id' => 105,
                'name' => 'Duration Timeframe 2',
                'description' => 'Second tracker with timeframe using duration',
                'item_name' => 'duration_timeframe_2',
            ]
        );
    }

    public function testAreTimeFrameSemanticsUsingSameTypeOfField(): void
    {
        $this->dao->save(self::$first_timeframe_using_end_date_tracker_id, 3937, null, 8146, null);
        $this->dao->save(self::$second_timeframe_using_end_date_tracker_id, 6074, null, 7121, null);
        $this->dao->save(self::$first_timeframe_using_duration_tracker_id, 5357, 1622, null, null);
        $this->dao->save(self::$second_timeframe_using_duration_tracker_id, 7411, 2812, null, null);

        $this->assertTrue(
            $this->dao->areTimeFrameSemanticsUsingSameTypeOfField(
                [self::$first_timeframe_using_duration_tracker_id, self::$second_timeframe_using_duration_tracker_id]
            )
        );
        $this->assertTrue(
            $this->dao->areTimeFrameSemanticsUsingSameTypeOfField(
                [self::$first_timeframe_using_end_date_tracker_id, self::$second_timeframe_using_end_date_tracker_id]
            )
        );
        $this->assertFalse(
            $this->dao->areTimeFrameSemanticsUsingSameTypeOfField(
                [self::$first_timeframe_using_end_date_tracker_id, self::$first_timeframe_using_duration_tracker_id]
            )
        );
        $this->assertFalse(
            $this->dao->areTimeFrameSemanticsUsingSameTypeOfField(
                [
                    self::$first_timeframe_using_duration_tracker_id,
                    self::$second_timeframe_using_duration_tracker_id,
                    self::$first_timeframe_using_end_date_tracker_id,
                    self::$second_timeframe_using_end_date_tracker_id,
                ]
            )
        );
    }
}
