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

namespace Tuleap\Test\PHPUnit;

use PermissionsManager;
use PluginManager;
use ProjectManager;
use Tracker_FormElementFactory;
use Tracker_ReportFactory;
use TrackerFactory;
use Tuleap\DB\DBFactory;
use UserManager;

abstract class TestIntegrationTestCase extends \Tuleap\Test\PHPUnit\TestCase
{
    private string $savepoint_id = "";

    /**
     * @before
     */
    public function setUpData(): void
    {
        parent::setUp();
        $this->savepoint_id = "save" . random_int(0, 99999999999);
        $db                 = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->beginTransaction();
        $db->run("SAVEPOINT " . $this->savepoint_id);
    }

    /**
     * @after
     */
    public function tearDownData(): void
    {
        parent::tearDown();
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run("ROLLBACK TO " . $this->savepoint_id);
        $db->rollBack();

        ProjectManager::clearInstance();
        PermissionsManager::clearInstance();
        PluginManager::clearInstance();
        UserManager::clearInstance();
        Tracker_FormElementFactory::clearInstance();
        TrackerFactory::clearInstance();
        Tracker_ReportFactory::clearInstance();

        unset($GLOBALS['_SESSION'], $GLOBALS['Language']);
    }
}
