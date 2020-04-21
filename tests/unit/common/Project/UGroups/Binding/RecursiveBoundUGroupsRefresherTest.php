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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class RecursiveBoundUGroupsRefresherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var RecursiveBoundUGroupsRefresherTest */
    private $recursive_refresher;
    /**
     * @var Mockery\MockInterface|BoundUGroupRefresher
     */
    private $refresher;
    /**
     * @var Mockery\MockInterface|\UGroupManager
     */
    private $ugroup_manager;

    protected function setUp(): void
    {
        $this->refresher           = Mockery::mock(BoundUGroupRefresher::class);
        $this->ugroup_manager      = Mockery::mock(\UGroupManager::class);
        $this->recursive_refresher = new RecursiveBoundUGroupsRefresher($this->refresher, $this->ugroup_manager);
    }

    public function testItRefreshesTheUGroup(): void
    {
        $source      = Mockery::mock(\ProjectUGroup::class, ['getId' => 149]);
        $destination = Mockery::mock(\ProjectUGroup::class, ['getId' => 371]);
        $this->refresher->shouldReceive('refresh')
            ->with($source, $destination)
            ->once();
        $this->ugroup_manager->shouldReceive('searchUGroupByBindingSource')
            ->with(371)
            ->once()
            ->andReturn([]);

        $this->recursive_refresher->refreshUGroupAndBoundUGroups($source, $destination);
    }

    public function testItRefreshesAllUGroupsBoundToTheGivenUGroup(): void
    {
        $source      = Mockery::mock(\ProjectUGroup::class, ['getId' => 149]);
        $destination = Mockery::mock(\ProjectUGroup::class, ['getId' => 371]);
        $this->refresher->shouldReceive('refresh')
            ->with($source, $destination)
            ->once();

        $first_bound_ugroup = Mockery::mock(\ProjectUGroup::class, ['getId' => 473]);
        $this->ugroup_manager->shouldReceive('getById')
            ->with(473)
            ->once()
            ->andReturn($first_bound_ugroup);
        $second_bound_ugroup = Mockery::mock(\ProjectUGroup::class, ['getId' => 623]);
        $this->ugroup_manager->shouldReceive('getById')
            ->with(623)
            ->once()
            ->andReturn($second_bound_ugroup);
        $this->ugroup_manager->shouldReceive('searchUGroupByBindingSource')
            ->with(371)
            ->once()
            ->andReturn([['ugroup_id' => 473], ['ugroup_id' => 623]]);

        $this->refresher->shouldReceive('refresh')
            ->with($destination, $first_bound_ugroup)
            ->once();
        $this->ugroup_manager->shouldReceive('searchUGroupByBindingSource')
            ->with(473)
            ->once()
            ->andReturn([]);
        $this->refresher->shouldReceive('refresh')
            ->with($destination, $second_bound_ugroup)
            ->once();
        $this->ugroup_manager->shouldReceive('searchUGroupByBindingSource')
            ->with(623)
            ->once()
            ->andReturn([]);

        $this->recursive_refresher->refreshUGroupAndBoundUGroups($source, $destination);
    }
}
