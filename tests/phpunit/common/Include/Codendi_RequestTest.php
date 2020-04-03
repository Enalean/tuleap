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
 *
 */

class Codendi_RequestTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $project_manager;
    private $project;

    public function setUp(): void
    {
        $this->project         = \Mockery::spy(\Project::class);
        $this->project_manager = \Mockery::spy(\ProjectManager::class)->shouldReceive('getProject')->with(123)->andReturns($this->project)->getMock();
    }

    public function testItReturnsTheProject()
    {
        $request = new Codendi_Request(array('group_id' => '123'), $this->project_manager);
        $this->assertEquals($this->project, $request->getProject());
    }

    public function testItReturnsNullIfInvalidRequestedGroupId()
    {
        $request = new Codendi_Request(array('group_id' => 'stuff'), $this->project_manager);
        $this->assertNull($request->getProject());
    }
}
