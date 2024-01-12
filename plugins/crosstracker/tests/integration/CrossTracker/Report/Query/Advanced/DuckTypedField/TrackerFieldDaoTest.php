<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField;

use Tuleap\DB\DBFactory;

final class TrackerFieldDaoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID       = 1;
    private const OTHER_TRACKER_ID = 2;
    private const FIELD_NAME       = 'my_field';
    private TrackerFieldDao $dao;

    public static function setUpBeforeClass(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->insert(
            'tracker_field',
            [
                'tracker_id' => self::TRACKER_ID,
                'formElement_type' => 'float',
                'name' => self::FIELD_NAME,
            ]
        );
        $db->insert(
            'tracker_field',
            [
                'tracker_id' => self::OTHER_TRACKER_ID,
                'formElement_type' => 'int',
                'name' => self::FIELD_NAME,
            ]
        );
    }

    public static function tearDownAfterClass(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM tracker_field');
    }

    protected function setUp(): void
    {
        $this->dao = new TrackerFieldDao();
    }

    public function testItFindsFieldTypes(): void
    {
        $tracker_ids = [self::TRACKER_ID, self::OTHER_TRACKER_ID];
        $types       = $this->dao->searchTypeByFieldNameAndTrackerList(self::FIELD_NAME, $tracker_ids);

        self::assertCount(2, $types);
        self::assertSame(DuckTypedFieldType::NUMERIC, $types[0]->unwrapOr(null));
        self::assertSame(DuckTypedFieldType::NUMERIC, $types[1]->unwrapOr(null));
    }
}
