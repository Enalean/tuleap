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

final class UGroupManagerGetUGroupWithMembersTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private int $ugroup_id;
    private \Project $project;
    private \UGroupManager&MockObject $ugroup_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ugroup_id = 112;
        $this->project   = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->build();

        $this->ugroup_manager = $this->createPartialMock(\UGroupManager::class, [
            'getUGroup',
        ]);
    }

    public function testItReturnsAUGroupWithMembers(): void
    {
        $ugroup = $this->createMock(\ProjectUGroup::class);
        $this->ugroup_manager->method('getUGroup')->with($this->project, $this->ugroup_id)->willReturn($ugroup);

        $ugroup->expects(self::once())->method('getMembers');

        $ugroup_with_members = $this->ugroup_manager->getUGroupWithMembers($this->project, $this->ugroup_id);
        self::assertSame($ugroup_with_members, $ugroup);
    }
}
