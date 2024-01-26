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

namespace Tuleap\TrackerFunctions\Administration;

use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class FunctionDaoTest extends TestIntegrationTestCase
{
    private const BUG_TRACKER_ID  = 1;
    private const TASK_TRACKER_ID = 2;

    private FunctionDao $dao;

    protected function setUp(): void
    {
        $this->dao = new FunctionDao();
    }

    public function testActivation(): void
    {
        self::assertFalse($this->dao->isFunctionActivated(self::BUG_TRACKER_ID));
        self::assertFalse($this->dao->isFunctionActivated(self::TASK_TRACKER_ID));

        $this->dao->activateFunction(self::BUG_TRACKER_ID);

        self::assertTrue($this->dao->isFunctionActivated(self::BUG_TRACKER_ID));
        self::assertFalse($this->dao->isFunctionActivated(self::TASK_TRACKER_ID));
    }

    public function testDeactivation(): void
    {
        $this->dao->activateFunction(self::BUG_TRACKER_ID);

        self::assertTrue($this->dao->isFunctionActivated(self::BUG_TRACKER_ID));
        self::assertFalse($this->dao->isFunctionActivated(self::TASK_TRACKER_ID));

        $this->dao->deactivateFunction(self::BUG_TRACKER_ID);

        self::assertFalse($this->dao->isFunctionActivated(self::BUG_TRACKER_ID));
        self::assertFalse($this->dao->isFunctionActivated(self::TASK_TRACKER_ID));
    }

    public function testShouldActivateOrDeactivateAnExistingEntry(): void
    {
        $this->dao->activateFunction(self::BUG_TRACKER_ID);
        $this->dao->deactivateFunction(self::BUG_TRACKER_ID);
        $this->dao->activateFunction(self::BUG_TRACKER_ID);

        self::assertTrue($this->dao->isFunctionActivated(self::BUG_TRACKER_ID));
    }
}
