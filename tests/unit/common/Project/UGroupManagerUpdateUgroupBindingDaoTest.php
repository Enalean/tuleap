<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\Project;

use PHPUnit\Framework\MockObject\MockObject;
use UGroupManager;

final class UGroupManagerUpdateUgroupBindingDaoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \UGroupDao&MockObject $dao;
    private UGroupManager $ugroup_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = $this->createMock(\UGroupDao::class);
        $this->dao->method('searchByUGroupId');
        $this->ugroup_manager = new UGroupManager($this->dao);
    }

    public function testItCallsDaoToRemoveABinding(): void
    {
        $this->dao->expects(self::once())->method('updateUgroupBinding')->with(12, null);
        $this->ugroup_manager->updateUgroupBinding(12);
    }

    public function testItCallsDaoToAddABinding(): void
    {
        $this->dao->expects(self::once())->method('updateUgroupBinding')->with(12, 24);
        $this->ugroup_manager->updateUgroupBinding(12, 24);
    }
}
