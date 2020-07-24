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
class UGroupManagerUpdateUgroupBindingEventTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var a|\Mockery\MockInterface|EventManager
     */
    private $event_manager;
    /**
     * @var ProjectUGroup
     */
    private $ugroup_12;
    /**
     * @var ProjectUGroup
     */
    private $ugroup_24;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao            = \Mockery::spy(\UGroupDao::class);
        $this->event_manager  = \Mockery::spy(\EventManager::class);
        $this->ugroup_manager = \Mockery::mock(
            \UGroupManager::class,
            [$this->dao, $this->event_manager]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->ugroup_12 = new ProjectUGroup(['ugroup_id' => 12]);
        $this->ugroup_24 = new ProjectUGroup(['ugroup_id' => 24]);
        $this->ugroup_manager->shouldReceive('getById')->with(12)->andReturns($this->ugroup_12);
        $this->ugroup_manager->shouldReceive('getById')->with(24)->andReturns($this->ugroup_24);
    }

    public function testItRaiseAnEventWithGroupsWhenOneIsAdded(): void
    {
        $this->event_manager->shouldReceive('processEvent')->with('ugroup_manager_update_ugroup_binding_add', ['ugroup' => $this->ugroup_12, 'source' => $this->ugroup_24])->once();
        $this->ugroup_manager->updateUgroupBinding(12, 24);
    }

    public function testItRaiseAnEventWithGroupsWhenOneIsRemoved(): void
    {
        $this->event_manager->shouldReceive('processEvent')->with('ugroup_manager_update_ugroup_binding_remove', ['ugroup' => $this->ugroup_12])->once();
        $this->ugroup_manager->updateUgroupBinding(12);
    }
}
