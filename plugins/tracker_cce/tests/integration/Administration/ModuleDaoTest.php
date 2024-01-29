<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\TrackerCCE\Administration;

use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class ModuleDaoTest extends TestIntegrationTestCase
{
    private const BUG_TRACKER_ID  = 1;
    private const TASK_TRACKER_ID = 2;

    private ModuleDao $dao;

    protected function setUp(): void
    {
        $this->dao = new ModuleDao();
    }

    public function testActivation(): void
    {
        self::assertFalse($this->dao->isModuleActivated(self::BUG_TRACKER_ID));
        self::assertFalse($this->dao->isModuleActivated(self::TASK_TRACKER_ID));

        $this->dao->activateModule(self::BUG_TRACKER_ID);

        self::assertTrue($this->dao->isModuleActivated(self::BUG_TRACKER_ID));
        self::assertFalse($this->dao->isModuleActivated(self::TASK_TRACKER_ID));
    }

    public function testDeactivation(): void
    {
        $this->dao->activateModule(self::BUG_TRACKER_ID);

        self::assertTrue($this->dao->isModuleActivated(self::BUG_TRACKER_ID));
        self::assertFalse($this->dao->isModuleActivated(self::TASK_TRACKER_ID));

        $this->dao->deactivateModule(self::BUG_TRACKER_ID);

        self::assertFalse($this->dao->isModuleActivated(self::BUG_TRACKER_ID));
        self::assertFalse($this->dao->isModuleActivated(self::TASK_TRACKER_ID));
    }

    public function testShouldActivateOrDeactivateAnExistingEntry(): void
    {
        $this->dao->activateModule(self::BUG_TRACKER_ID);
        $this->dao->deactivateModule(self::BUG_TRACKER_ID);
        $this->dao->activateModule(self::BUG_TRACKER_ID);

        self::assertTrue($this->dao->isModuleActivated(self::BUG_TRACKER_ID));
    }
}
