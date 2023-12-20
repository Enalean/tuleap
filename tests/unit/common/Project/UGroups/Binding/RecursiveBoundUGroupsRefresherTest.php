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
 */

declare(strict_types=1);

namespace Tuleap\Project\UGroups\Binding;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;

final class RecursiveBoundUGroupsRefresherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private RecursiveBoundUGroupsRefresher $recursive_refresher;
    private BoundUGroupRefresher&MockObject $refresher;
    private \UGroupManager&MockObject $ugroup_manager;

    protected function setUp(): void
    {
        $this->refresher           = $this->createMock(BoundUGroupRefresher::class);
        $this->ugroup_manager      = $this->createMock(\UGroupManager::class);
        $this->recursive_refresher = new RecursiveBoundUGroupsRefresher($this->refresher, $this->ugroup_manager);
    }

    public function testItRefreshesTheUGroup(): void
    {
        $source      = ProjectUGroupTestBuilder::aCustomUserGroup(149)->build();
        $destination = ProjectUGroupTestBuilder::aCustomUserGroup(371)->build();
        $this->refresher
            ->expects(self::once())
            ->method('refresh')
            ->with($source, $destination);
        $this->ugroup_manager
            ->expects(self::once())
            ->method('searchUGroupByBindingSource')
            ->with(371)
            ->willReturn([]);

        $this->recursive_refresher->refreshUGroupAndBoundUGroups($source, $destination);
    }

    public function testItRefreshesAllUGroupsBoundToTheGivenUGroup(): void
    {
        $source      = ProjectUGroupTestBuilder::aCustomUserGroup(149)->build();
        $destination = ProjectUGroupTestBuilder::aCustomUserGroup(371)->build();

        $first_bound_ugroup  = ProjectUGroupTestBuilder::aCustomUserGroup(473)->build();
        $second_bound_ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(623)->build();
        $this->ugroup_manager
            ->expects(self::exactly(2))
            ->method('getById')
            ->withConsecutive([473], [623])
            ->willReturnOnConsecutiveCalls(
                $first_bound_ugroup,
                $second_bound_ugroup
            );

        $this->refresher
            ->expects(self::exactly(3))
            ->method('refresh')
            ->withConsecutive(
                [$source, $destination],
                [$destination, $first_bound_ugroup],
                [$destination, $second_bound_ugroup]
            );
        $this->ugroup_manager
            ->expects(self::exactly(3))
            ->method('searchUGroupByBindingSource')
            ->withConsecutive([371], [473], [623])
            ->willReturnOnConsecutiveCalls([['ugroup_id' => 473], ['ugroup_id' => 623]], [], []);

        $this->recursive_refresher->refreshUGroupAndBoundUGroups($source, $destination);
    }
}
