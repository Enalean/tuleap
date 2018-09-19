<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../bootstrap.php';

class Git_GitoliteHousekeeping_GitoliteHousekeepingGitGcTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->dao    = mock('Git_GitoliteHousekeeping_GitoliteHousekeepingDao');
        $this->logger = mock('Logger');

        $this->gitgc = partial_mock(
            'Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc',
            array('execGitGcAsAppAdm'),
            array(
                $this->dao,
                $this->logger,
                '/path/to/gitolite_admin_working_copy'
            )
        );
    }

    public function itRunsGitGcIfItIsAllowed() {
        stub($this->dao)->isGitGcEnabled()->returns(true);

        expect($this->logger)->info('Running git gc on gitolite admin working copy.')->once();
        expect($this->gitgc)->execGitGcAsAppAdm()->once();

        $this->gitgc->cleanUpGitoliteAdminWorkingCopy();
    }

    public function itDoesNotRunGitGcIfItIsNotAllowed() {
        stub($this->dao)->isGitGcEnabled()->returns(false);

        expect($this->logger)->warn(
            'Cannot run git gc on gitolite admin working copy. '.
            'Please run as root: /usr/share/tuleap/src/utils/php-launcher.sh '.
            '/usr/share/tuleap/plugins/git/bin/gl-admin-housekeeping.php'
        )->once();
        expect($this->gitgc)->execGitGcAsAppAdm()->never();

        $this->gitgc->cleanUpGitoliteAdminWorkingCopy();
    }
}
