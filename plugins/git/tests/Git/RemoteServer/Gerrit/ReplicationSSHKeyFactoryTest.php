<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */
require_once dirname(__FILE__).'/../../../../include/constants.php';
require_once GIT_BASE_DIR .'/Git/RemoteServer/Gerrit/ReplicationSSHKeyFactory.class.php';
require_once GIT_BASE_DIR .'/Git/RemoteServer/Gerrit/ReplicationSSHKey.class.php';
require_once GIT_BASE_DIR .'/Git_Exec.class.php';

class Git_RemoteServer_Gerrit_ReplicationSSHKeyFactoryTest extends TuleapTestCase {
    
    public function testSaveWillNotAddsReplicationKeyThatHasNoHostId() {
        $key = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $git_executor = mock('Git_Exec');
        $factory = new Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory($git_executor);

        stub($git_executor)->add()->never();

        $factory->save($key);
}

    public function testSaveWillNotAddsReplicationKeyThatHasNoUserName() {

    }

    public function testSaveWillNotAnEmptyValuedReplicationKey() {

    }

}

?>
