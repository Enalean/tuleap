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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class UGroupManagerGetUGroupWithMembersTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private int $ugroup_id;
    private Project $project;
    /**
     * @var \Mockery\Mock&\Mockery\MockInterface&UGroupManager
     */
    private $ugroup_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ugroup_id = 112;
        $this->project   = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->build();

        $this->ugroup_manager = \Mockery::mock(\UGroupManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testItReturnsAUGroupWithMembers(): void
    {
        $ugroup = \Mockery::spy(\ProjectUGroup::class);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, $this->ugroup_id)->andReturns($ugroup);

        $ugroup->shouldReceive('getMembers')->once();

        $ugroup_with_members = $this->ugroup_manager->getUGroupWithMembers($this->project, $this->ugroup_id);
        $this->assertSame($ugroup_with_members, $ugroup);
    }
}
