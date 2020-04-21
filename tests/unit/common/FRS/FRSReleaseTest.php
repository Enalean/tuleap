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

class FRSReleaseTest extends \PHPUnit\Framework\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testIsActive()
    {
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;

        $r = new FRSRelease();
        $r->setStatusId($active_value);
        $this->assertTrue($r->isActive());

        $r->setStatusId($hidden_value);
        $this->assertFalse($r->isActive());

        $r->setStatusId($deleted_value);
        $this->assertFalse($r->isActive());
    }

    public function testIsHidden()
    {
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;

        $r = new FRSRelease();
        $r->setStatusId($hidden_value);
        $this->assertTrue($r->isHidden());

        $r->setStatusId($active_value);
        $this->assertFalse($r->isHidden());

        $r->setStatusId($deleted_value);
        $this->assertFalse($r->isHidden());
    }

    public function testIsDeleted()
    {
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;

        $r = new FRSRelease();
        $r->setStatusId($deleted_value);
        $this->assertTrue($r->isDeleted());

        $r->setStatusId($hidden_value);
        $this->assertFalse($r->isDeleted());

        $r->setStatusId($active_value);
        $this->assertFalse($r->isDeleted());
    }

    public function testGetProjectWithProjectSet()
    {
        $r = new FRSRelease();

        $p = new Project(['group_id' => 101]);
        $r->setProject($p);

        $this->assertSame($p, $r->getProject());
    }

    public function testGetProjectWithGroupIdSet()
    {
        $r = \Mockery::mock(\FRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->setGroupID(123);

        $p = new Project(['group_id' => 101]);

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(123)->once()->andReturns($p);

        $r->shouldReceive('_getProjectManager')->andReturns($pm);

        $this->assertSame($p, $r->getProject());
    }

    public function testGetProjectWithNeitherProjectNorGroupID()
    {
        $r = \Mockery::mock(\FRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->setPackageId(696);

        $pkg = new FRSPackage(array('group_id' => 123));

        $pf = \Mockery::spy(\FRSPackageFactory::class);
        $pf->shouldReceive('getFRSPackageFromDb')->with(696, null, FRSPackageDao::INCLUDE_DELETED)->once()->andReturns($pkg);
        $r->shouldReceive('_getFRSPackageFactory')->andReturns($pf);

        $p = new Project(['group_id' => 101]);
        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProject')->with(123)->once()->andReturns($p);
        $r->shouldReceive('_getProjectManager')->andReturns($pm);

        $this->assertSame($p, $r->getProject());
    }

    public function testGetGroupIdWithoutProjectSet()
    {
        $r = \Mockery::mock(\FRSRelease::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->setPackageId(696);

        $pkg = new FRSPackage(array('group_id' => 123));

        $pf = \Mockery::spy(\FRSPackageFactory::class);
        $pf->shouldReceive('getFRSPackageFromDb')->with(696, null, FRSPackageDao::INCLUDE_DELETED)->once()->andReturns($pkg);
        $r->shouldReceive('_getFRSPackageFactory')->andReturns($pf);

        $this->assertSame($r->getGroupID(), 123);
    }

    public function testGetGroupIdWithProjectSet()
    {
        $r = new FRSRelease();

        $p = \Mockery::spy(\Project::class);
        $p->shouldReceive('getID')->andReturns(123);
        $r->setProject($p);

        $this->assertSame($r->getGroupID(), 123);
    }
}
