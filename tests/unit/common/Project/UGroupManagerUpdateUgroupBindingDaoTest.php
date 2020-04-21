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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class UGroupManagerUpdateUgroupBindingDaoTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao            = \Mockery::spy(\UGroupDao::class);
        $this->ugroup_manager = new UGroupManager($this->dao);
    }

    public function testItCallsDaoToRemoveABinding(): void
    {
        $this->dao->shouldReceive('updateUgroupBinding')->with(12, null)->once();
        $this->ugroup_manager->updateUgroupBinding(12);
    }

    public function testItCallsDaoToAddABinding(): void
    {
        $this->dao->shouldReceive('updateUgroupBinding')->with(12, 24)->once();
        $this->ugroup_manager->updateUgroupBinding(12, 24);
    }
}
