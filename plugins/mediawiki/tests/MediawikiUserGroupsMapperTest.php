<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once 'bootstrap.php';

require_once dirname(__FILE__).'/../include/MediawikiUserGroupsMapper.class.php';

class MediawikiUserGroupsMapperTest extends TuleapTestCase {

    /** @var MediawikiDao */
    private $dao;

    /** @var MediawikiUserGroupsMapper */
    private $mapper;

    /** @var Project */
    private $project;

    public function setUp() {
        parent::setUp();

        $this->dao             = mock('MediawikiDao');
        $this->forge_perms_dao = mock('User_ForgeUserGroupPermissionsDao');
        $this->mapper          = new MediawikiUserGroupsMapper($this->dao, $this->forge_perms_dao);
        $this->project         = mock('Project');
    }

    public function itAddsProjectMembersAsBots() {
        stub($this->dao)->getMediawikiUserGroupMapping()->returnsDar(
            array('group_id' => '104', 'ugroup_id' => '1', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS)
        );

        $new_mapping = array(
            'anonymous'  => array(
                '1',
            ),
            'bot'        => array(
                '3'
            ),
            'user'       => array(),
            'sysop'      => array(),
            'bureaucrat' => array()
        );

        expect($this->dao)->addMediawikiUserGroupMapping($this->project, MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT, '3')->once();
        expect($this->dao)->removeMediawikiUserGroupMapping()->never();
        $this->mapper->saveMapping($new_mapping, $this->project);
    }

    public function itRemovesRegisteredUsersFromBot() {
        stub($this->dao)->getMediawikiUserGroupMapping()->returnsDar(
            array('group_id' => '104', 'ugroup_id' => '1', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS),
            array('group_id' => '104', 'ugroup_id' => '2', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER),
            array('group_id' => '104', 'ugroup_id' => '3', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT)
        );

        $new_mapping = array(
            'anonymous'  => array(
                '1'
            ),
            'user'       => array(
                '2'
            ),
            'bot'        => array(),
            'sysop'      => array(),
            'bureaucrat' => array(),
        );

        expect($this->dao)->removeMediawikiUserGroupMapping($this->project, MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT, '3')->once();
        expect($this->dao)->addMediawikiUserGroupMapping()->never();
        $this->mapper->saveMapping($new_mapping, $this->project);
    }

    public function itIgnoresAnonymousModifications() {
        stub($this->dao)->getMediawikiUserGroupMapping()->returnsDar(
            array('group_id' => '104', 'ugroup_id' => '1', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS)
        );

        $new_mapping = array(
            'anonymous'  => array(),
            'bot'        => array(),
            'user'       => array(),
            'sysop'      => array(),
            'bureaucrat' => array()
        );

        expect($this->dao)->removeMediawikiUserGroupMapping()->never();
        expect($this->dao)->addMediawikiUserGroupMapping()->never();
        $this->mapper->saveMapping($new_mapping, $this->project);
    }

    public function itIgnoresUserModifications() {
        stub($this->dao)->getMediawikiUserGroupMapping()->returnsDar(
            array('group_id' => '104', 'ugroup_id' => '1', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS),
            array('group_id' => '104', 'ugroup_id' => '2', 'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER)
        );

        $new_mapping = array(
            'anonymous'  => array(),
            'bot'        => array(),
            'user'       => array(),
            'sysop'      => array(),
            'bureaucrat' => array()
        );

        expect($this->dao)->removeMediawikiUserGroupMapping()->never();
        expect($this->dao)->addMediawikiUserGroupMapping()->never();
        $this->mapper->saveMapping($new_mapping, $this->project);
    }

    public function itCallsRemoveAndAddDAOMethodsDuringSave() {
        stub($this->dao)->getMediawikiUserGroupMapping()->returnsDar(
            array(
                'group_id'      => 104,
                'ugroup_id'     => 1,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 2,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_SYSOP
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT
            )
        );

        $new_mapping = array(
            'anonymous'  => array(
                '1'
            ),
            'user'       => array(
                '2',
            ),
            'bot'        => array(
                '3',
                '2',
                '4'
            ),
            'sysop'      => array(
                '1'
            ),
            'bureaucrat' => array(
                '1'
            )
        );

        expect($this->dao)->removeMediawikiUserGroupMapping()->count(2);
        expect($this->dao)->removeMediawikiUserGroupMapping($this->project, MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_SYSOP,      4)->at(0);
        expect($this->dao)->removeMediawikiUserGroupMapping($this->project, MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT, 4)->at(1);

        expect($this->dao)->addMediawikiUserGroupMapping()->count(5);
        expect($this->dao)->addMediawikiUserGroupMapping($this->project, MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT, '1')->at(4);
        $this->mapper->saveMapping($new_mapping, $this->project);
    }

    public function itReturnsTrueIfCurrentMappingEqualsDefaultOneForPublicProject() {
        $current_mapping = array (
            array(
                'group_id'      => 104,
                'ugroup_id'     => 1,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 2,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 3,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_SYSOP
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT
            )
        );

        stub($this->dao)->getMediawikiUserGroupMapping()->returns($current_mapping);
        stub($this->project)->isPublic()->returns(true);

        $is_default = $this->mapper->isDefaultMapping($this->project);
        $this->assertTrue($is_default);
    }

    public function itReturnsFalseIfCurrentMappingNotEqualsDefaultOneForPublicProject() {
        $current_mapping = array (
            array(
                'group_id'      => 104,
                'ugroup_id'     => 1,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_ANONYMOUS
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 2,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT
            )
        );

        stub($this->dao)->getMediawikiUserGroupMapping()->returns($current_mapping);
        stub($this->project)->isPublic()->returns(true);

        $is_default = $this->mapper->isDefaultMapping($this->project);
        $this->assertFalse($is_default);
    }

    public function itReturnsTrueIfCurrentMappingEqualsDefaultOneForPrivateProject() {
        $current_mapping = array (
            array(
                'group_id'      => 104,
                'ugroup_id'     => 3,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_USER
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_SYSOP
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT
            )
        );

        stub($this->dao)->getMediawikiUserGroupMapping()->returns($current_mapping);
        stub($this->project)->isPublic()->returns(false);

        $is_default = $this->mapper->isDefaultMapping($this->project);
        $this->assertTrue($is_default);
    }

    public function itReturnsFalseIfCurrentMappingNotEqualsDefaultOneForPrivateProject() {
        $current_mapping = array (
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BOT
            ),
            array(
                'group_id'      => 104,
                'ugroup_id'     => 4,
                'mw_group_name' => MediawikiUserGroupsMapper::MEDIAWIKI_GROUPS_BUREAUCRAT
            )
        );

        stub($this->dao)->getMediawikiUserGroupMapping()->returns($current_mapping);
        stub($this->project)->isPublic()->returns(false);

        $is_default = $this->mapper->isDefaultMapping($this->project);
        $this->assertFalse($is_default);
    }
}

class MediawikiUserGroupsMapper_DefineGroupsTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->tuleap_user    = stub('PFUser')->getId()->returns(101);
        $this->project        = stub(Project::class)->getId()->returns(202);
        $this->dao            = mock('MediawikiDao');
        $this->forge_pems_dao = mock('User_ForgeUserGroupPermissionsDao');
        $this->mapper         = new MediawikiUserGroupsMapper($this->dao, $this->forge_pems_dao);
    }

    public function itReturnsRightMediawikiGroupsFromDatabase() {
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returnsEmptyDar();
        stub($this->dao)->getMediawikiGroupsMappedForUGroups($this->tuleap_user, $this->project)->returnsDar(
            array('real_name' => 'sysop'),
            array('real_name' => 'bureaucrat')
        );

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertEqual($mediawiki_groups, array(
            'added' => array(
                '*',
                'sysop',
                'bureaucrat'
            ),
            'removed' => array(
            )
        ));
    }

    public function itSetAnonymousUsersAsAnonymous() {
        stub($this->tuleap_user)->isAnonymous()->returns(true);
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returnsEmptyDar();

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertEqual($mediawiki_groups, array(
            'added' => array(
                '*',
            ),
            'removed' => array(
            )
        ));
    }

    public function itSetAnonymousWhenNothingIsAvailable() {
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returnsEmptyDar();
        stub($this->dao)->getMediawikiGroupsMappedForUGroups($this->tuleap_user, $this->project)->returnsEmptyDar();

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertEqual($mediawiki_groups, array(
            'added' => array(
                '*',
            ),
            'removed' => array(
            )
        ));
    }

    public function itReturnsUnconsistantMediawikiGroupsToBeDeleted() {
        stub($this->tuleap_user)->isMember(202, 'A')->returns(true);
        stub($this->dao)->getMediawikiGroupsMappedForUGroups($this->tuleap_user, $this->project)->returnsEmptyDar();
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returnsDar(
            array('ug_group' => 'ForgeRole:forge_admin')
        );

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertEqual($mediawiki_groups['removed'], array('ForgeRole:forge_admin'));
    }

    public function itRevokesGroupsTheUserIsNoLongerMemberOf() {
        stub($this->tuleap_user)->isMember(202, 'A')->returns(true);
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returnsDar(
            array('ug_group' => 'bureaucrat')
        );
        stub($this->dao)->getMediawikiGroupsMappedForUGroups($this->tuleap_user, $this->project)->returnsEmptyDar();

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertEqual($mediawiki_groups['removed'], array('bureaucrat'));
    }

    public function itDoesntAddGroupsTheUserAlreadyHave() {
        stub($this->tuleap_user)->isMember(202, 'A')->returns(true);
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returnsDar(
            array('ug_group' => '*')
        );
        stub($this->dao)->getMediawikiGroupsMappedForUGroups($this->tuleap_user, $this->project)->returnsDar(
            array('real_name' => '*')
        );

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertEqual($mediawiki_groups, array('added' => array(), 'removed' => array()));
    }
}
