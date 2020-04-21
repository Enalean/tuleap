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

class FRSPackageTest extends \PHPUnit\Framework\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    public function testIsActive()
    {
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;

        $p = new FRSPackage();
        $p->setStatusId($active_value);
        $this->assertTrue($p->isActive());

        $p->setStatusId($hidden_value);
        $this->assertFalse($p->isActive());

        $p->setStatusId($deleted_value);
        $this->assertFalse($p->isActive());
    }

    public function testIsDeleted()
    {
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;

        $p = new FRSPackage();
        $p->setStatusId($deleted_value);
        $this->assertTrue($p->isDeleted());

        $p->setStatusId($hidden_value);
        $this->assertFalse($p->isDeleted());

        $p->setStatusId($active_value);
        $this->assertFalse($p->isDeleted());
    }

    public function testIsHidden()
    {
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;

        $p = new FRSPackage();
        $p->setStatusId($hidden_value);
        $this->assertTrue($p->isHidden());

        $p->setStatusId($active_value);
        $this->assertFalse($p->isHidden());

        $p->setStatusId($deleted_value);
        $this->assertFalse($p->isHidden());
    }
}
