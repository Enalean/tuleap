<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\LDAP\REST;

use Tuleap\Project\REST\UserGroupAdditionalInformationEvent;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LDAPUserGroupRepresentationInformationAdderTest extends TestCase
{
    /**
     * @var \LDAP_ProjectGroupManager&\PHPUnit\Framework\MockObject\Stub
     */
    private mixed $project_ugroup_manager;
    /**
     * @var \LDAP_ProjectGroupDao&\PHPUnit\Framework\MockObject\Stub
     */
    private mixed $project_user_group_dao;
    /**
     * @var \LDAP_UserGroupManager&\PHPUnit\Framework\MockObject\Stub
     */
    private mixed $static_ugroup_manager;
    /**
     * @var \LDAP_UserGroupDao&\PHPUnit\Framework\MockObject\Stub
     */
    private mixed $static_user_group_dao;

    private LDAPUserGroupRepresentationInformationAdder $event_processor;

    protected function setUp(): void
    {
        $this->project_ugroup_manager = $this->createStub(\LDAP_ProjectGroupManager::class);
        $this->project_user_group_dao = $this->createStub(\LDAP_ProjectGroupDao::class);
        $this->static_ugroup_manager  = $this->createStub(\LDAP_UserGroupManager::class);
        $this->static_user_group_dao  = $this->createStub(\LDAP_UserGroupDao::class);

        $this->event_processor = new LDAPUserGroupRepresentationInformationAdder(
            $this->project_ugroup_manager,
            $this->project_user_group_dao,
            $this->static_ugroup_manager,
            $this->static_user_group_dao
        );
    }

    public function testProjectAdministratorGetLDAPInformation(): void
    {
        $user = $this->createStub(\PFUser::class);
        $user->method('isAdmin')->willReturn(true);
        $user->method('isSuperUser')->willReturn(false);

        $this->static_user_group_dao->method('searchByGroupId')->willReturn(['synchro_policy' => 'never', 'bind_option' => 'bind']);
        $this->static_ugroup_manager->method('getLdapGroupByGroupId')->willReturn(new \LDAPResult(['grp_display_name' => ['Static ugroup']], ['grp_display_name' => 'grp_display_name']));

        $event = $this->getLDAPInformation($user, new \ProjectUGroup(['ugroup_id' => 150, 'group_id' => 102]));

        self::assertEquals(
            new LDAPUserGroupRepresentationProjectAdministrator(
                'Static ugroup',
                'never',
                'bind'
            ),
            $event->additional_information['ldap'],
        );
    }

    public function testSiteAdministratorGetLDAPInformation(): void
    {
        $user = $this->createStub(\PFUser::class);
        $user->method('isAdmin')->willReturn(true);
        $user->method('isSuperUser')->willReturn(true);

        $this->project_user_group_dao->method('searchByGroupId')->willReturn(['synchro_policy' => 'auto', 'bind_option' => 'preserve_members']);
        $this->project_ugroup_manager->method('getLdapGroupByGroupId')->willReturn(
            new \LDAPResult(['grp_display_name' => ['SomeProjectMembers'], 'dn' => 'cn=developers,ou=groups,dc=tuleap,dc=local'], ['grp_display_name' => 'grp_display_name'])
        );

        $event = $this->getLDAPInformation($user, new \ProjectUGroup(['ugroup_id' => \ProjectUGroup::PROJECT_MEMBERS, 'group_id' => 102]));

        self::assertEquals(
            new LDAPUserGroupRepresentationSiteAdministrator(
                'SomeProjectMembers',
                'auto',
                'preserve_members',
                'cn=developers,ou=groups,dc=tuleap,dc=local'
            ),
            $event->additional_information['ldap']
        );
    }

    public function testNoInformationIsAddedWhenItIsADynamicUserGroupThatIsNotProjectMembers(): void
    {
        $event = $this->getLDAPInformation(
            UserTestBuilder::anActiveUser()->build(),
            new \ProjectUGroup(['ugroup_id' => \ProjectUGroup::PROJECT_ADMIN])
        );

        self::assertEmpty($event->additional_information);
    }

    public function testNoInformationIsAddedWhenTheUserIsNotProjectAdmin(): void
    {
        $user = $this->createStub(\PFUser::class);
        $user->method('isAdmin')->willReturn(false);

        $event = $this->getLDAPInformation(
            $user,
            new \ProjectUGroup(['ugroup_id' => \ProjectUGroup::PROJECT_MEMBERS])
        );

        self::assertEmpty($event->additional_information);
    }

    public function testSetsInformationWhenUserGroupIsNotLinkedToAnLDAPGroup(): void
    {
        $user = $this->createStub(\PFUser::class);
        $user->method('isAdmin')->willReturn(true);
        $user->method('isSuperUser')->willReturn(false);

        $this->project_user_group_dao->method('searchByGroupId')->willReturn(false);
        $this->project_ugroup_manager->method('getLdapGroupByGroupId')->willReturn(null);

        $event = $this->getLDAPInformation(
            $user,
            new \ProjectUGroup(['ugroup_id' => \ProjectUGroup::PROJECT_MEMBERS])
        );

        self::assertNull($event->additional_information['ldap']);
    }

    private function getLDAPInformation(\PFUser $current_user, \ProjectUGroup $ugroup): UserGroupAdditionalInformationEvent
    {
        $event = new UserGroupAdditionalInformationEvent($ugroup, $current_user);

        $this->event_processor->addAdditionalUserGroupInformation($event);

        return $event;
    }
}
