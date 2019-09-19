<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class UGroupLiteralizerTest extends TuleapTestCase
{

    protected $membership;
    protected $user;
    public const PERMISSIONS_TYPE = 'PLUGIN_DOCMAN_%';

    public function setUp()
    {
        parent::setUp();
        $this->user   = mock('PFUser');
        $user_manager = mock('UserManager');
        stub($user_manager)->getUserByUserName()->returns($this->user);
        UserManager::setInstance($user_manager);
        $this->ugroup_literalizer = new UGroupLiteralizer();
    }

    public function tearDown()
    {
        UserManager::clearInstance();
        parent::tearDown();
    }

    public function itIsProjectMember()
    {
        stub($this->user)->getStatus()->returns('A');
        $userProjects = array(
                array('group_id'=>101, 'unix_group_name'=>'gpig1')
        );
        stub($this->user)->getProjects()->returns($userProjects);
        stub($this->user)->isMember()->returns(false);
        stub($this->user)->getAllUgroups()->returnsEmptyDar();

        $this->assertUserGroupsForUser(array('site_active','gpig1_project_members'));
    }

    public function itIsProjectAdmin()
    {
        stub($this->user)->getStatus()->returns('A');
        $userProjects = array(
                array('group_id'=>102, 'unix_group_name'=>'gpig2')
        );
        stub($this->user)->getProjects()->returns($userProjects);
        stub($this->user)->isMember()->returns(true);
        stub($this->user)->getAllUgroups()->returnsEmptyDar();

        $this->assertUserGroupsForUser(array('site_active','gpig2_project_members', 'gpig2_project_admin'));
    }

    public function itIsMemberOfAStaticUgroup()
    {
        stub($this->user)->getStatus()->returns('A');
        stub($this->user)->getProjects()->returns(array());
        stub($this->user)->isMember()->returns(false);
        stub($this->user)->getAllUgroups()->returnsDar(array('ugroup_id'=>304));

        $this->assertUserGroupsForUser(array('site_active','ug_304'));
    }

    public function itIsRestricted()
    {
        stub($this->user)->getStatus()->returns('R');
        stub($this->user)->getProjects()->returns(array());
        stub($this->user)->isMember()->returns(false);
        stub($this->user)->getAllUgroups()->returnsEmptyDar();

        $this->assertUserGroupsForUser(array('site_restricted'));
    }


    public function itIsNeitherRestrictedNorActive()
    {
        stub($this->user)->getStatus()->returns('Not exists');
        stub($this->user)->getProjects()->returns(array());
        stub($this->user)->isMember()->returns(false);
        stub($this->user)->getAllUgroups()->returnsEmptyDar();

        $this->assertUserGroupsForUser(array());
    }

    private function assertUserGroupsForUser(array $expected)
    {
        $this->assertEqual($expected, $this->ugroup_literalizer->getUserGroupsForUserName('john_do'));
        $this->assertEqual($expected, $this->ugroup_literalizer->getUserGroupsForUser($this->user));
    }

    public function itCanTransformAnArrayWithUGroupMembersConstantIntoString()
    {
        $ugroup_ids = array(ProjectUGroup::PROJECT_MEMBERS);
        $expected   = array('@gpig_project_members');
        $this->assertUgroupIdsToString($ugroup_ids, $expected);
    }

    public function itDoesntIncludeTwiceProjectMemberIfSiteActive()
    {
        $ugroup_ids = array(ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_MEMBERS);
        $expected   = array('@site_active', '@gpig_project_members');
        $this->assertUgroupIdsToString($ugroup_ids, $expected);
    }

    private function assertUgroupIdsToString($ugroup_ids, $expected)
    {
        $project = mock('Project');
        stub($project)->getUnixName()->returns('gpig');

        $result = $this->ugroup_literalizer->ugroupIdsToString($ugroup_ids, $project);
        $this->assertEqual($expected, $result);
    }

    public function itCanReturnUgroupIdsFromAnItemAndItsPermissionTypes()
    {
        $object_id = 100;
        $expected  = array(ProjectUGroup::PROJECT_MEMBERS);
        $project   = mock('Project');
        $permissions_manager = mock('PermissionsManager');
        stub($permissions_manager)->getAuthorizedUGroupIdsForProject($project, $object_id, self::PERMISSIONS_TYPE)->returns($expected);
        PermissionsManager::setInstance($permissions_manager);
        $result = $this->ugroup_literalizer->getUgroupIds($project, $object_id, self::PERMISSIONS_TYPE);
        $this->assertEqual($expected, $result);
        PermissionsManager::clearInstance();
    }
}
