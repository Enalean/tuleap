<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\FRS;

use FRSPackage;
use FRSPackageDao;
use FRSPackageFactory;
use FRSRelease;
use Project;
use ProjectManager;
use Tuleap\Test\PHPUnit\TestCase;

class FRSReleaseTest extends TestCase
{
    public function testIsActive(): void
    {
        $active_value  = 1;
        $deleted_value = 2;
        $hidden_value  = 3;

        $r = new FRSRelease();
        $r->setStatusId($active_value);
        self::assertTrue($r->isActive());

        $r->setStatusId($hidden_value);
        self::assertFalse($r->isActive());

        $r->setStatusId($deleted_value);
        self::assertFalse($r->isActive());
    }

    public function testIsHidden(): void
    {
        $active_value  = 1;
        $deleted_value = 2;
        $hidden_value  = 3;

        $r = new FRSRelease();
        $r->setStatusId($hidden_value);
        self::assertTrue($r->isHidden());

        $r->setStatusId($active_value);
        self::assertFalse($r->isHidden());

        $r->setStatusId($deleted_value);
        self::assertFalse($r->isHidden());
    }

    public function testIsDeleted(): void
    {
        $active_value  = 1;
        $deleted_value = 2;
        $hidden_value  = 3;

        $r = new FRSRelease();
        $r->setStatusId($deleted_value);
        self::assertTrue($r->isDeleted());

        $r->setStatusId($hidden_value);
        self::assertFalse($r->isDeleted());

        $r->setStatusId($active_value);
        self::assertFalse($r->isDeleted());
    }

    public function testGetProjectWithProjectSet(): void
    {
        $r = new FRSRelease();

        $p = new Project(['group_id' => 101]);
        $r->setProject($p);

        self::assertSame($p, $r->getProject());
    }

    public function testGetProjectWithGroupIdSet(): void
    {
        $r = $this->createPartialMock(FRSRelease::class, [
            '_getProjectManager',
        ]);
        $r->setGroupID(123);

        $p = new Project(['group_id' => 101]);

        $pm = $this->createMock(ProjectManager::class);
        $pm->expects(self::once())->method('getProject')->with(123)->willReturn($p);

        $r->method('_getProjectManager')->willReturn($pm);

        self::assertSame($p, $r->getProject());
    }

    public function testGetProjectWithNeitherProjectNorGroupID(): void
    {
        $r = $this->createPartialMock(FRSRelease::class, [
            '_getFRSPackageFactory',
            '_getProjectManager',
        ]);
        $r->setPackageId(696);

        $pkg = new FRSPackage(['group_id' => 123]);

        $pf = $this->createMock(FRSPackageFactory::class);
        $pf->expects(self::once())->method('getFRSPackageFromDb')->with(696, null, FRSPackageDao::INCLUDE_DELETED)->willReturn($pkg);
        $r->method('_getFRSPackageFactory')->willReturn($pf);

        $p  = new Project(['group_id' => 101]);
        $pm = $this->createMock(ProjectManager::class);
        $pm->expects(self::once())->method('getProject')->with(123)->willReturn($p);
        $r->method('_getProjectManager')->willReturn($pm);

        self::assertSame($p, $r->getProject());
    }

    public function testGetGroupIdWithoutProjectSet(): void
    {
        $r = $this->createPartialMock(FRSRelease::class, [
            '_getFRSPackageFactory',
        ]);
        $r->setPackageId(696);

        $pkg = new FRSPackage(['group_id' => 123]);

        $pf = $this->createMock(FRSPackageFactory::class);
        $pf->expects(self::once())->method('getFRSPackageFromDb')->with(696, null, FRSPackageDao::INCLUDE_DELETED)->willReturn($pkg);
        $r->method('_getFRSPackageFactory')->willReturn($pf);

        self::assertSame($r->getGroupID(), 123);
    }

    public function testGetGroupIdWithProjectSet(): void
    {
        $r = new FRSRelease();

        $p = $this->createMock(Project::class);
        $p->method('getID')->willReturn(123);
        $r->setProject($p);

        self::assertSame($r->getGroupID(), 123);
    }
}
