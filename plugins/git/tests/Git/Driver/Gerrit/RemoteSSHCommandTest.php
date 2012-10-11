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

require_once dirname(__FILE__).'/../../../../include/constants.php';
require_once GIT_BASE_DIR . '/Git/Driver/Gerrit/RemoteSSHCommand.class.php';

class Git_Driver_Gerrit_RemoteSSHCommand_Test extends TuleapTestCase {

    protected $host = 'gerrit.example.com';
    
    protected $port = '29418';

    protected $login = 'gerrit';

    protected $identity_file = '/path/to/codendiadm/.ssh/id_rsa';

    /**
     * @var RemoteSshCommand
     */
    protected $ssh;

    public function setUp() {
        parent::setUp();
        $this->ssh = partial_mock(
            'Git_Driver_Gerrit_RemoteSSHCommand',
            array('sshExec'),
            array($this->host, $this->port, $this->login, $this->identity_file)
        );
    }

    public function itExecutesTheCreateCommandOnTheGerritServer() {
        expect($this->ssh)->sshExec('-p 29418 -i /path/to/codendiadm/.ssh/id_rsa gerrit@gerrit.example.com a_remote_command')->once();
        $this->ssh->execute('a_remote_command');
    }
}
?>
