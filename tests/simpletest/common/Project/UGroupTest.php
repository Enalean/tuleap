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

require_once __DIR__ . '/../../../../src/www/project/admin/ugroup_utils.php';
require_once __DIR__ . '/../../../../src/www/include/utils.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class UGroup_RemoveUserTest extends TuleapTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->user_id = 400;
        $this->user    = stub('PFUser')->getId()->returns($this->user_id);
    }

    public function itRemoveUserFromStaticGroup()
    {
        $ugroup_id = 200;
        $group_id  = 300;

        $ugroup = TestHelper::getPartialMock('ProjectUGroup', ['removeUserFromStaticGroup', 'exists']);
        stub($ugroup)->exists()->returns(true);
        $ugroup->__construct(['ugroup_id' => $ugroup_id, 'group_id' => $group_id]);

        $ugroup->expectOnce('removeUserFromStaticGroup', [$group_id, $ugroup_id, $this->user_id]);

        $ugroup->removeUser($this->user);
    }

    public function itThrowAnExceptionIfStaticUGroupDoesntExist()
    {
        $ugroup_id = 200;
        $group_id  = 300;

        $ugroup = TestHelper::getPartialMock('ProjectUGroup', ['exists']);
        stub($ugroup)->exists()->returns(false);
        $ugroup->__construct(['ugroup_id' => $ugroup_id, 'group_id' => $group_id]);

        $this->expectException(new UGroup_Invalid_Exception());

        $ugroup->removeUser($this->user);
    }

    public function itRemovesUserFromDynamicGroup(): void
    {
        $ugroup_id = $GLOBALS['UGROUP_WIKI_ADMIN'];
        $group_id  = 300;

        $ugroup = Mockery::mock(ProjectUGroup::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $ugroup->shouldReceive('removeUserFromDynamicGroup')->with($this->user)->once();

        $ugroup->__construct(['ugroup_id' => $ugroup_id, 'group_id' => $group_id]);

        $ugroup->removeUser($this->user);
    }

    public function itThrowAnExceptionIfThereIsNoGroupId()
    {
        $ugroup_id = 200;

        $ugroup = new ProjectUGroup(['ugroup_id' => $ugroup_id]);

        $this->expectException();

        $ugroup->removeUser($this->user);
    }

    public function itThrowAnExceptionIfThereIsNoUGroupId()
    {
        $group_id = 300;

        $ugroup = new ProjectUGroup(['group_id' => $group_id]);

        $this->expectException();

        $ugroup->removeUser($this->user);
    }

    public function itThrowAnExceptionIfUserIsNotValid()
    {
        $group_id  = 300;
        $ugroup_id = 200;

        $ugroup = new ProjectUGroup(['group_id' => $group_id, 'ugroup_id' => $ugroup_id]);

        $this->expectException();

        $user = anAnonymousUser()->build();

        $ugroup->removeUser($user);
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Classes.ClassDeclaration.MultipleClasses
class UGroup_getUsersBaseTest extends TuleapTestCase
{
    protected $garfield;
    protected $goofy;
    protected $garfield_incomplete_row = ['user_id' => 1234, 'user_name' => 'garfield'];
    protected $goofy_incomplete_row    = ['user_id' => 5677, 'user_name' => 'goofy'];

    public function setUp()
    {
        parent::setUp();
        $user_manager = mock('UserManager');
        UserManager::setInstance($user_manager);

        $this->garfield = new PFUser($this->garfield_incomplete_row);
        $this->goofy    = new PFUser($this->goofy_incomplete_row);
        stub($user_manager)->getUserById($this->garfield_incomplete_row['user_id'])->returns($this->garfield);
        stub($user_manager)->getUserById($this->goofy_incomplete_row['user_id'])->returns($this->goofy);
    }

    public function tearDown()
    {
        UserManager::clearInstance();
        parent::tearDown();
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Classes.ClassDeclaration.MultipleClasses
class UGroup_getUsersTest extends UGroup_getUsersBaseTest
{

    public function itIsEmptyWhenTheGroupIsEmpty()
    {
        $id     = 333;
        $row    = ['ugroup_id' => $id, 'group_id' => 105];
        $ugroup = new ProjectUGroup($row);
        $ugroup->setUGroupUserDao(stub('UGroupUserDao')->searchUserByStaticUGroupId($id)->returnsEmptyDar());
        $this->assertEqual($ugroup->getUsers()->getNames(), []);
    }

    public function itReturnsTheMembersOfStaticGroups()
    {
        $id     = 333;
        $row    = ['ugroup_id' => $id, 'group_id' => 105];
        $ugroup = new ProjectUGroup($row);
        $ugroup->setUGroupUserDao(
            stub('UGroupUserDao')->searchUserByStaticUGroupId($id)->returnsDar(
                $this->garfield_incomplete_row,
                $this->goofy_incomplete_row
            )
        );

        $this->assertEqual($ugroup->getUsers()->getNames(), ['garfield', 'goofy']);
    }

    public function itReturnsTheMembersOfDynamicGroups()
    {
        $id       = 1;
        $group_id = 555;
        $row      = ['ugroup_id' => $id, 'group_id' => $group_id];
        $ugroup   = new ProjectUGroup($row);
        $ugroup->setUGroupUserDao(
            stub('UGroupUserDao')->searchUserByDynamicUGroupId($id, $group_id)->returnsDar(
                $this->garfield_incomplete_row,
                $this->goofy_incomplete_row
            )
        );

        $this->assertEqual($ugroup->getUsers()->getNames(), ['garfield', 'goofy']);
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Classes.ClassDeclaration.MultipleClasses
class UGroup_getMembersTest extends UGroup_getUsersBaseTest
{

    public function itIsEmptyWhenTheGroupIsEmpty()
    {
        $id     = 333;
        $row    = ['ugroup_id' => $id];
        $ugroup = new ProjectUGroup($row);
        $ugroup->setUGroupUserDao(stub('UGroupUserDao')->searchUserByStaticUGroupId($id)->returnsEmptyDar());
        $this->assertTrue(count($ugroup->getMembers()) == 0);
        $this->assertTrue(count($ugroup->getMembersUserName()) == 0);
    }

    public function itReturnsTheMembersOfStaticGroups()
    {
        $id     = 333;
        $row    = ['ugroup_id' => $id];
        $ugroup = new ProjectUGroup($row);
        $ugroup->setUGroupUserDao(
            stub('UGroupUserDao')->searchUserByStaticUGroupId($id)->returnsDar(
                $this->garfield_incomplete_row,
                $this->goofy_incomplete_row
            )
        );
        $this->assertArrayNotEmpty($ugroup->getMembers());
        $this->assertEqual(count($ugroup->getMembers()), 2);

        $this->assertArrayNotEmpty($ugroup->getMembersUserName());
        $this->assertArrayNotEmpty($ugroup->getMembersUserName(), ['garfiel', $this->goofy_incomplete_row]);
    }

    public function itReturnsTheMembersOfDynamicGroups()
    {
        $id       = 1;
        $group_id = 555;
        $row      = ['ugroup_id' => $id, 'group_id' => $group_id];
        $ugroup   = new ProjectUGroup($row);
        $ugroup->setUGroupUserDao(
            stub('UGroupUserDao')->searchUserByDynamicUGroupId($id, $group_id)->returnsDar(
                $this->garfield_incomplete_row,
                $this->goofy_incomplete_row
            )
        );
        $this->assertArrayNotEmpty($ugroup->getMembers());
        $this->assertEqual(count($ugroup->getMembers()), 2);

        $this->assertArrayNotEmpty($ugroup->getMembersUserName());
        $this->assertArrayNotEmpty($ugroup->getMembersUserName(), ['garfiel', $this->goofy_incomplete_row]);
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Classes.ClassDeclaration.MultipleClasses
class UGroup_GetsNameTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setText('membre_de_projet', ['project_ugroup', 'ugroup_project_members']);
        $this->setText('administrateur_de_le_projet', ['project_ugroup', 'ugroup_project_admins']);
    }

    public function itReturnsProjectMembers()
    {
        $ugroup = new ProjectUGroup(
            ['ugroup_id' => ProjectUGroup::PROJECT_MEMBERS, 'name' => 'ugroup_project_members_name_key']
        );
        $this->assertEqual('ugroup_project_members_name_key', $ugroup->getName());
        $this->assertEqual('membre_de_projet', $ugroup->getTranslatedName());
        $this->assertEqual('project_members', $ugroup->getNormalizedName());
    }

    public function itReturnsProjectAdmins()
    {
        $ugroup = new ProjectUGroup(
            ['ugroup_id' => ProjectUGroup::PROJECT_ADMIN, 'name' => 'ugroup_project_admins_name_key']
        );
        $this->assertEqual('ugroup_project_admins_name_key', $ugroup->getName());
        $this->assertEqual('administrateur_de_le_projet', $ugroup->getTranslatedName());
        $this->assertEqual('project_admins', $ugroup->getNormalizedName());
    }

    public function itReturnsAStaticGroup()
    {
        $ugroup = new ProjectUGroup(['ugroup_id' => 120, 'name' => 'Zoum_zoum_zen']);
        $this->assertEqual('Zoum_zoum_zen', $ugroup->getName());
        $this->assertEqual('Zoum_zoum_zen', $ugroup->getTranslatedName());
        $this->assertEqual('Zoum_zoum_zen', $ugroup->getNormalizedName());
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Classes.ClassDeclaration.MultipleClasses
class UGroup_SourceInitializationTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->ugroup = partial_mock('ProjectUGroup', ['getUgroupBindingSource'], ['ugroup_id' => 123]);
    }

    public function itQueriesTheDatabaseWhenDefaultValueIsFalse()
    {
        expect($this->ugroup)->getUgroupBindingSource()->once();
        $this->ugroup->isBound();
    }

    public function itQueriesTheDatabaseOnlyOnce()
    {
        expect($this->ugroup)->getUgroupBindingSource()->once();
        stub($this->ugroup)->getUgroupBindingSource()->returns(null);
        $this->ugroup->isBound();
        $this->ugroup->isBound();
    }

    public function itReturnsTrueWhenTheGroupIsBound()
    {
        stub($this->ugroup)->getUgroupBindingSource()->returns(stub('ProjectUGroup')->getId()->returns(666));
        $this->assertTrue($this->ugroup->isBound());
    }

    public function itReturnsFalseWhenTheGroupIsNotBound()
    {
        stub($this->ugroup)->getUgroupBindingSource()->returns(null);
        $this->assertFalse($this->ugroup->isBound());
    }
}
