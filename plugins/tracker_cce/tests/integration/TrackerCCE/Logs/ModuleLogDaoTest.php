<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\TrackerCCE\Logs;

use Tuleap\DB\DBFactory;

final class ModuleLogDaoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ModuleLogDao $dao;

    protected function setUp(): void
    {
        $this->dao = new ModuleLogDao();
    }

    protected function tearDown(): void
    {
        DBFactory::getMainTuleapDBConnection()->getDB()
            ->run('DELETE FROM plugin_tracker_cce_module_log');
    }

    public function testItCanInsertANewPassedEntry(): void
    {
        $this->dao->saveModuleLogLine(ModuleLogLine::buildPassed(
            856,
            '{"source": "payload"}',
            '{"generated": "payload"}',
            1234567890,
        ));

        $result = DBFactory::getMainTuleapDBConnection()->getDB()
            ->row('SELECT * FROM plugin_tracker_cce_module_log WHERE changeset_id = ?', 856);

        self::assertIsArray($result);
        self::assertEquals(ModuleLogLine::STATUS_PASSED, $result['status']);
        self::assertEquals(856, $result['changeset_id']);
        self::assertEquals('{"source": "payload"}', $result['source_payload_json']);
        self::assertEquals('{"generated": "payload"}', $result['generated_payload_json']);
        self::assertNull($result['error_message']);
        self::assertEquals(1234567890, $result['execution_date']);
    }

    public function testItCanInsertANewErrorEntry(): void
    {
        $this->dao->saveModuleLogLine(ModuleLogLine::buildError(
            856,
            '{"source": "payload"}',
            'Error message',
            1234567890,
        ));

        $result = DBFactory::getMainTuleapDBConnection()->getDB()
            ->row('SELECT * FROM plugin_tracker_cce_module_log WHERE changeset_id = ?', 856);

        self::assertIsArray($result);
        self::assertEquals(ModuleLogLine::STATUS_ERROR, $result['status']);
        self::assertEquals(856, $result['changeset_id']);
        self::assertEquals('{"source": "payload"}', $result['source_payload_json']);
        self::assertNull($result['generated_payload_json']);
        self::assertEquals('Error message', $result['error_message']);
        self::assertEquals(1234567890, $result['execution_date']);
    }
}
