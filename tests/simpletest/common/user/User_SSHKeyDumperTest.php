<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/user/User_SSHKeyDumper.class.php';

class User_SSHKeyDumperTest extends TuleapTestCase {
    private $toto_name;
    private $toto_home;
    private $foobar_home;
    private $key;
    private $user;
    private $sshkey_dumper;
    private $backend;

    public function setUp() {
        parent::setUp();

        $GLOBALS['homedir_prefix'] = dirname(__FILE__).'/_fixtures/users';
        $this->toto_name = 'toto';
        $this->toto_home = $GLOBALS['homedir_prefix'].'/'.$this->toto_name;
        mkdir($this->toto_home);

        $this->foobar_home = $GLOBALS['homedir_prefix'].'/foobar';
        mkdir($this->foobar_home.'/.ssh', 0751, true);
        touch($this->foobar_home.'/.ssh/authorized_keys');

        $this->backend = partial_mock('Backend', array('log', 'chown', 'chgrp'));

        $this->key  = 'bla';
        $this->user = aUser()->withUserName($this->toto_name)
                             ->withAuthorizedKeysArray(array($this->key))
                             ->withUnixStatus('A')
                             ->build();
        $this->sshkey_dumper = partial_mock(
            'User_SSHKeyDumper',
            array(
                'changeProcessUidGidToUser',
                'restoreRootUidGid'
            ));
        $this->sshkey_dumper->__construct($this->backend);
    }

    public function tearDown() {
        parent::tearDown();

        EventManager::clearInstance();
        unset($GLOBALS['homedir_prefix']);
        $backend = Backend::instance();
        $backend->recurseDeleteInDir($this->toto_home);
        rmdir($this->toto_home);

        $backend->recurseDeleteInDir($this->foobar_home);
        rmdir($this->foobar_home);
    }

    public function itWriteTheKeyInTheAutorizedKeyFile() {
        stub($this->backend)->log(new PatternExpectation('/Authorized_keys for '.$this->toto_name.' written/'), 'info')->once();

        $this->backend->expectCallCount('chown', 2);
        stub($this->backend)->chown($this->toto_home.'/.ssh', $this->toto_name)->at(0);
        stub($this->backend)->chown($this->toto_home.'/.ssh/authorized_keys', $this->toto_name)->at(1);

        $this->backend->expectCallCount('chgrp', 2);
        stub($this->backend)->chgrp($this->toto_home.'/.ssh', $this->toto_name)->at(0);
        stub($this->backend)->chgrp($this->toto_home.'/.ssh/authorized_keys', $this->toto_name)->at(1);

        $this->sshkey_dumper->writeSSHKeys($this->user);
        $this->assertEqual($this->key, file_get_contents($this->toto_home.'/.ssh/authorized_keys'));
        $this->assertEqual('0700', $this->getFileModeAsString($this->toto_home.'/.ssh'));
        $this->assertEqual('0600', $this->getFileModeAsString($this->toto_home.'/.ssh/authorized_keys'));

    }

    public function itDoesntModifyFilesWhenUserMadeASymlink() {
        // The user tries to compromise the system, it has an SSH account on the
        // server and made a link from its own authorized_keys file onto someoneelse
        // example /home/users/toto/.ssh/authorized_keys -> /root/.ssh/authorized_keys
        // if following test fails, it means that a mere user can use it to ssh
        // the machine as root!
        mkdir($this->toto_home.'/.ssh', 0751, true);
        symlink($this->foobar_home.'/.ssh/authorized_keys', $this->toto_home.'/.ssh/authorized_keys');

        $this->sshkey_dumper->writeSSHKeys($this->user);
        $this->assertEqual($this->key, file_get_contents($this->toto_home.'/.ssh/authorized_keys'));
        $this->assertEqual('', file_get_contents($this->foobar_home.'/.ssh/authorized_keys'));
        $this->assertFalse(is_link($this->toto_home.'/.ssh/authorized_keys'));
        $this->assertFalse(is_link($this->foobar_home.'/.ssh/authorized_keys'));
    }

    public function itRaisesAnErrorWhenUserAttemptedToMakeALinkOnSshDir() {
        // variation of previous test but user did:
        // /home/users/toto/.ssh -> /root/.ssh
        symlink($this->foobar_home.'/.ssh', $this->toto_home.'/.ssh');

        stub($this->backend)->log('*', 'error')->once();

        $this->sshkey_dumper->writeSSHKeys($this->user);
    }

    public function itDoesntModifyDirectoriesWhenUserMadeASymlink() {
        // variation of previous test but user did:
        // /home/users/toto/.ssh -> /root/.ssh
        symlink($this->foobar_home.'/.ssh', $this->toto_home.'/.ssh');

        // First call will fail (see previous test) ...
        $this->sshkey_dumper->writeSSHKeys($this->user);

        $this->assertFalse(is_link($this->toto_home.'/.ssh'));
        $this->assertFalse(is_link($this->foobar_home.'/.ssh'));

        // ... so execute twice to see is things are properly cleaned-up
        $this->sshkey_dumper->writeSSHKeys($this->user);

        $this->assertEqual($this->key, file_get_contents($this->toto_home.'/.ssh/authorized_keys'));
        $this->assertEqual('', file_get_contents($this->foobar_home.'/.ssh/authorized_keys'));
    }

    private function getFileModeAsString($filename) {
        return substr(sprintf('%o', fileperms($filename)), -4);
    }
}
?>
