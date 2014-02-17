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

    public function itReturnsRightMediawikiGroupsFromDatabase() {
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returns(array());
        stub($this->dao)->getMediawikiGroupsMappedForUGroups($this->tuleap_user, $this->project)->returnsDar(
            array('real_name' => 'sysop'),
            array('real_name' => 'bureaucrat')
        );

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertEqual($mediawiki_groups, array(
            'added' => array(
                'sysop',
                'bureaucrat'
            ),
            'removed' => array(
            )
        ));
    }

    public function itSetAnonymousUsersAsAnonymous() {
        stub($this->tuleap_user)->isAnonymous()->returns(true);
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returns(array());

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
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returns(array());
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
        stub($this->dao)->getMediawikiGroupsForUser($this->tuleap_user, $this->project)->returns(array('ForgeRole:forge_admin'));

        $mediawiki_groups = $this->mapper->defineUserMediawikiGroups($this->tuleap_user, $this->project);

        $this->assertEqual($mediawiki_groups['removed'], array('ForgeRole:forge_admin'));
    }

}
