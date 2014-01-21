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

class MediawikiGroupsMapperTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->tuleap_user = stub('PFUser')->getId()->returns(101);
        $this->project     = stub('Group')->getId()->returns(202);
        $this->dao         = mock('MediawikiDao');
        $this->mapper      = new MediawikiGroupsMapper($this->dao);
    }

    public function itReturnsRightMediawikiGroupsForProjectAdmins() {
        stub($this->tuleap_user)->isMember(202, 'A')->returns(true);
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returns(array());

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertTrue(in_array('sysop', $mediawiki_groups['added']));
        $this->assertTrue(in_array('bureaucrat', $mediawiki_groups['added']));
        $this->assertTrue(empty($mediawiki_groups['deleted']));
    }

    public function itReturnsRightMediawikiGroupsForProjectMembers() {
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returns(array());
        stub($this->project)->isPublic()->returns(true);

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertTrue(in_array('user', $mediawiki_groups['added']));
        $this->assertTrue(in_array('autoconfirmed', $mediawiki_groups['added']));
        $this->assertTrue(in_array('emailconfirmed', $mediawiki_groups['added']));
        $this->assertFalse(in_array('sysop', $mediawiki_groups['added']));
        $this->assertFalse(in_array('bureaucrat', $mediawiki_groups['added']));
    }

    public function itReturnsRightMediawikiGroupsForAnonymousUserInPublicProject() {
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returns(array());
        stub($this->project)->isPublic()->returns(true);
        stub($this->tuleap_user)->isAnonymous()->returns(true);

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertTrue(in_array('*', $mediawiki_groups['added']));
        $this->assertFalse(in_array('user', $mediawiki_groups['added']));
        $this->assertFalse(in_array('autoconfirmed', $mediawiki_groups['added']));
        $this->assertFalse(in_array('emailconfirmed', $mediawiki_groups['added']));
        $this->assertFalse(in_array('sysop', $mediawiki_groups['added']));
        $this->assertFalse(in_array('bureaucrat', $mediawiki_groups['added']));
    }

    public function itReturnsRightMediawikiGroupsForAnonymousUserInPrivateProject() {
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returns(array());
        stub($this->tuleap_user)->isAnonymous()->returns(true);

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertTrue(in_array('*', $mediawiki_groups['added']));
        $this->assertFalse(in_array('user', $mediawiki_groups['added']));
        $this->assertFalse(in_array('autoconfirmed', $mediawiki_groups['added']));
        $this->assertFalse(in_array('emailconfirmed', $mediawiki_groups['added']));
        $this->assertFalse(in_array('sysop', $mediawiki_groups['added']));
        $this->assertFalse(in_array('bureaucrat', $mediawiki_groups['added']));
    }

    public function itReturnsRightMediawikiGroupsForRegisteredUsersInPublicProject() {
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returns(array());
        stub($this->project)->isPublic()->returns(true);

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertTrue(in_array('user', $mediawiki_groups['added']));
        $this->assertTrue(in_array('autoconfirmed', $mediawiki_groups['added']));
        $this->assertTrue(in_array('emailconfirmed', $mediawiki_groups['added']));
        $this->assertFalse(in_array('sysop', $mediawiki_groups['added']));
        $this->assertFalse(in_array('bureaucrat', $mediawiki_groups['added']));
    }

    public function itReturnsRightMediawikiGroupsForRegisteredUsersInPrivateProject() {
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returns(array());

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertTrue(in_array('*', $mediawiki_groups['added']));
        $this->assertFalse(in_array('user', $mediawiki_groups['added']));
        $this->assertFalse(in_array('autoconfirmed', $mediawiki_groups['added']));
        $this->assertFalse(in_array('emailconfirmed', $mediawiki_groups['added']));
        $this->assertFalse(in_array('sysop', $mediawiki_groups['added']));
        $this->assertFalse(in_array('bureaucrat', $mediawiki_groups['added']));
    }

    public function itReturnsUnconsistantMediawikiGroupsToBeDeleted() {
        stub($this->tuleap_user)->isMember(202, 'A')->returns(true);
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returns(array('ForgeRole:forge_admin'));

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertTrue(in_array('ForgeRole:forge_admin', $mediawiki_groups['removed']));
    }

}
