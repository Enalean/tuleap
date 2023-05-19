<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class UGroupRemoveUserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private int $user_id = 400;
    private PFUser $user;
    private PFUser $project_administrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user                  = \Tuleap\Test\Builders\UserTestBuilder::aUser()->withId($this->user_id)->build();
        $this->project_administrator = \Tuleap\Test\Builders\UserTestBuilder::aUser()->withId(9857)->build();
    }

    public function testItRemoveUserFromStaticGroup(): void
    {
        $ugroup_id = 200;
        $group_id  = 300;

        $ugroup = \Mockery::mock(\ProjectUGroup::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ugroup->shouldReceive('exists')->andReturns(true);
        $ugroup->__construct(['ugroup_id' => $ugroup_id, 'group_id' => $group_id]);

        $ugroup->shouldReceive('removeUserFromStaticGroup')->with($group_id, $ugroup_id, $this->user_id)->once();

        $ugroup->removeUser($this->user, $this->project_administrator);
    }

    public function testItThrowAnExceptionIfStaticUGroupDoesntExist(): void
    {
        $ugroup_id = 200;
        $group_id  = 300;

        $ugroup = \Mockery::mock(\ProjectUGroup::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ugroup->shouldReceive('exists')->andReturns(false);
        $ugroup->__construct(['ugroup_id' => $ugroup_id, 'group_id' => $group_id]);

        $this->expectException(UGroup_Invalid_Exception::class);

        $ugroup->removeUser($this->user, $this->project_administrator);
    }

    public function testItRemovesUserFromDynamicGroup(): void
    {
        $ugroup_id = $GLOBALS['UGROUP_WIKI_ADMIN'];
        $group_id  = 300;

        $ugroup = Mockery::mock(ProjectUGroup::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ugroup->shouldReceive('removeUserFromDynamicGroup')->with($this->user, $this->project_administrator)->once();

        $ugroup->__construct(['ugroup_id' => $ugroup_id, 'group_id' => $group_id]);

        $ugroup->removeUser($this->user, $this->project_administrator);
    }

    public function testItThrowAnExceptionIfThereIsNoGroupId(): void
    {
        $ugroup_id = 200;

        $ugroup = new ProjectUGroup(['ugroup_id' => $ugroup_id]);

        $this->expectException(Exception::class);

        $ugroup->removeUser($this->user, $this->project_administrator);
    }

    public function testItThrowAnExceptionIfThereIsNoUGroupId(): void
    {
        $group_id = 300;

        $ugroup = new ProjectUGroup(['group_id' => $group_id]);

        $this->expectException(UGroup_Invalid_Exception::class);

        $ugroup->removeUser($this->user, $this->project_administrator);
    }

    public function testItThrowAnExceptionIfUserIsNotValid(): void
    {
        $group_id  = 300;
        $ugroup_id = 200;

        $ugroup = new ProjectUGroup(['group_id' => $group_id, 'ugroup_id' => $ugroup_id]);

        $this->expectException(Exception::class);

        $user = new PFUser(['user_id' => 0, 'language_id' => 'en_US']);

        $ugroup->removeUser($user, $this->project_administrator);
    }
}
