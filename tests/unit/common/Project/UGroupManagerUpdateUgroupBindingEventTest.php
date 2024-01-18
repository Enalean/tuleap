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
use ProjectUGroup;

final class UGroupManagerUpdateUgroupBindingEventTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \EventManager&MockObject $event_manager;
    private ProjectUGroup $ugroup_12;
    private ProjectUGroup $ugroup_24;
    private \UGroupDao&MockObject $dao;
    private \UGroupManager&MockObject $ugroup_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = $this->createMock(\UGroupDao::class);
        $this->dao->method('updateUgroupBinding');
        $this->event_manager  = $this->createMock(\EventManager::class);
        $this->ugroup_manager = $this->getMockBuilder(\UGroupManager::class)
            ->setConstructorArgs([$this->dao, $this->event_manager])
            ->onlyMethods([
                'getById',
            ])
            ->getMock();

        $this->ugroup_12 = new ProjectUGroup(['ugroup_id' => 12]);
        $this->ugroup_24 = new ProjectUGroup(['ugroup_id' => 24]);
        $this->ugroup_manager->method('getById')->withConsecutive([12], [24])
            ->willReturnOnConsecutiveCalls($this->ugroup_12, $this->ugroup_24);
    }

    public function testItRaiseAnEventWithGroupsWhenOneIsAdded(): void
    {
        $this->event_manager->expects(self::once())->method('processEvent')
            ->with('ugroup_manager_update_ugroup_binding_add', ['ugroup' => $this->ugroup_12, 'source' => $this->ugroup_24]);
        $this->ugroup_manager->updateUgroupBinding(12, 24);
    }

    public function testItRaiseAnEventWithGroupsWhenOneIsRemoved(): void
    {
        $this->event_manager->expects(self::once())->method('processEvent')
            ->with('ugroup_manager_update_ugroup_binding_remove', ['ugroup' => $this->ugroup_12]);
        $this->ugroup_manager->updateUgroupBinding(12);
    }
}
