<?php
/**
 * Copyright (c) Enalean, 2012-present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

final class Tracker_FormElement_Field_List_Bind_UsersValueTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;

    public function testGetLabel(): void
    {
        $uh = Mockery::mock(UserHelper::class);
        $uh->shouldReceive('getDisplayNameFromUserId')->withArgs([12])->andReturn('John Smith');

        $bv = $this->getListBindUserValue();

        $bv->shouldReceive('getUserHelper')->andReturn($uh);

        $this->assertEquals('John Smith', $bv->getLabel());
    }

    public function testGetUser(): void
    {
        $u = Mockery::mock(PFUser::class);

        $uh = Mockery::mock(UserManager::class);
        $uh->shouldReceive('getUserById')->withArgs(array(12))->andReturn($u);

        $bv = $this->getListBindUserValue();
        $bv->shouldReceive('getUserManager')->andReturn($uh);

        $this->assertEquals($u, $bv->getUser());
    }

    public function testItReturnsTheUserNameAsWell(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getUserName')->andReturn('neo');
        $user->shouldReceive('getRealName')->andReturn('Le roi arthur');
        $user->shouldReceive('getAvatarUrl')->andReturn('');
        $user->shouldReceive('isNone')->andReturn(false);

        $user_manager = Mockery::mock(UserManager::class);
        $user_manager->shouldReceive('getUserById')->withArgs(array(12))->andReturn($user);

        $user_helper = Mockery::mock(UserHelper::class);
        $user_helper->shouldReceive('getDisplayNameFromUserId')->andReturn('Thomas A. Anderson (neo)');

        $value = $this->getListBindUserValue();
        $value->shouldReceive('getUserManager')->andReturn($user_manager);
        $value->shouldReceive('getUserHelper')->andReturn($user_helper);

        $json = $value->fetchFormattedForJson();
        $this->assertEquals(
            [
                'id'           => '12',
                'label'        => 'Thomas A. Anderson (neo)',
                'is_hidden'    => false,
                'username'     => 'neo',
                'realname'     => 'Le roi arthur',
                'avatar_url'   => '',
                'display_name' => 'Le roi arthur (neo)'
            ],
            $json
        );
    }

    public function testItReturnsNullForGetJsonIfUserIsNone(): void
    {
        $value = new Tracker_FormElement_Field_List_Bind_UsersValue(100, 'none', 'none');
        $json = $value->getJsonValue();
        $this->assertNull($json);
    }

    /**
     * @return \Mockery\Mock|Tracker_FormElement_Field_List_Bind_UsersValue
     */
    private function getListBindUserValue()
    {
        $bind = Mockery::mock(Tracker_FormElement_Field_List_Bind_UsersValue::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $bind->shouldReceive('getId')->andReturn(12);

        return $bind;
    }
}
