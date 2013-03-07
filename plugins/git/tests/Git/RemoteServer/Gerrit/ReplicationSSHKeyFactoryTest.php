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

    private $key_user_name;
    /**
     *
     * @var Git_RemoteServer_Gerrit_ReplicationSSHKey
     */
    private $key;

    /**
     *
     * @var Git_Exec
     */
    private $git_executor;

    /**
     *
     * @var Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory
     */
    private $factory;

    private $gitolite_directoy;

    public function setUp() {
        parent::setUp();

        $this->key_user_name = 'someone'.  rand(1025, 999999);

        $this->key = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $this->key->setGerritHostId(25)
            ->setUserName($this->key_user_name)
            ->setValue('abc');

        $this->gitolite_directoy = '/var/tmp';

        if (!is_dir('/var/tmp/key')) {
            exec('mkdir /var/tmp/key');
        }

        $this->git_executor = mock('Git_Exec');
        stub($this->git_executor)->getPath()->returns($this->gitolite_directoy);
        $this->factory = new Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory($this->git_executor);
    }


    public function testSaveWillNotAddsReplicationKeyThatHasNoHostId() {
        $this->key->setGerritHostId(null);

        stub($this->git_executor)->add()->never();

        $this->factory->save($this->key);
    }

    public function testSaveWillNotAddsReplicationKeyThatHasNoUserName() {
        $this->key->setUserName(null);

        stub($this->git_executor)->add()->never();

        $this->factory->save($this->key);
    }

    public function testSaveWillNotAnEmptyValuedReplicationKey() {
        $this->key->setValue(null);

        stub($this->git_executor)->add()->never();

        $this->factory->save($this->key);
    }

    public function testSaveWillGitAddValidReplicationKey() {
        stub($this->git_executor)->add()->once();

        $this->factory->save($this->key);
    }

    public function testSaveWillCreateKeyFile() {
        $file = $this->gitolite_directoy . '/key/' . $this->key->getUserName() . '.pub';
        $this->assertFalse(is_file($file));

        $this->factory->save($this->key);

        $this->assertTrue(is_file($file));
        $file_contents = file_get_contents($file);
        $this->assertEqual($file_contents, $this->key->getValue());
    }

}

?>
