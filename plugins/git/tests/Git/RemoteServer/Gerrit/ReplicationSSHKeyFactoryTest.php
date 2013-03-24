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

class Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory_SaveTest extends TuleapTestCase {

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
            ->setValue('abc');

        $this->gitolite_directoy = '/var/tmp';

        $key_dir = Git_Gitolite_SSHKeyDumper::KEYDIR;

        if (!is_dir('/var/tmp/'.$key_dir)) {
            exec('mkdir /var/tmp/'.$key_dir);
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

    public function testSaveWillDeleteAnEmptyValuedReplicationKey() {
        $this->key->setValue(null);

        $factory = partial_mock('Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory', array('deleteForGerritServerId'), array($this->git_executor));
        stub($factory)->deleteForGerritServerId($this->key->getGerritHostId())->once();

        $factory->save($this->key);
    }

    public function testSaveWillGitAddValidReplicationKey() {
        stub($this->git_executor)->add()->once();

        $this->factory->save($this->key);
    }

    public function testSaveWillGitCommitValidReplicationKey() {
        stub($this->git_executor)->commit()->once();

        $this->factory->save($this->key);
    }

    public function testSaveWillGitPushValidReplicationKey() {
        stub($this->git_executor)->push()->once();

        $this->factory->save($this->key);
    }

    public function testSaveWillCreateKeyFile() {
        $key_dir         = Git_Gitolite_SSHKeyDumper::KEYDIR;
        $key_filename    = $this->key->getGitoliteKeyFile();

        $file = $this->gitolite_directoy . '/'.$key_dir.'/' . $key_filename;
        $this->assertFalse(is_file($file));

        $this->factory->save($this->key);

        $this->assertTrue(is_file($file));
        $file_contents = file_get_contents($file);
        $this->assertEqual($file_contents, $this->key->getValue());
    }

    public function testSaveWillOverwriteExistingKeyFile() {
        $key_dir         = Git_Gitolite_SSHKeyDumper::KEYDIR;
        $key_filename    = $this->key->getGitoliteKeyFile();

        $file = $this->gitolite_directoy . '/'.$key_dir.'/' . $key_filename;
        $this->assertFalse(is_file($file));

        $this->factory->save($this->key);

        $this->assertTrue(is_file($file));
        $file_contents = file_get_contents($file);
        $this->assertEqual($file_contents, $this->key->getValue());

        $new_value = 'I am the new key value';
        $this->key->setValue($new_value);
        $this->factory->save($this->key);

        $this->assertTrue(is_file($file));
        $new_file_contents = file_get_contents($file);
        $this->assertEqual($new_file_contents, $new_value);
    }

    public function tearDown() {
        parent::tearDown();

        $key_dir = Git_Gitolite_SSHKeyDumper::KEYDIR;
        exec('rm -rf ' . $this->gitolite_directoy.'/'.$key_dir);
    }

}

class Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory_FetchForGerritServerIdTest extends TuleapTestCase {

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

        $this->git_executor = mock('Git_Exec');
        $this->gitolite_directoy = '/var/tmp';
        $key_dir = Git_Gitolite_SSHKeyDumper::KEYDIR;

        if (!is_dir('/var/tmp/'.$key_dir)) {
            exec('mkdir /var/tmp/'.$key_dir);
        }

        stub($this->git_executor)->getPath()->returns($this->gitolite_directoy);
        $this->factory = new Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory($this->git_executor);
    }

    public function itReturnsAReplicationSSHKey() {
        $id = 98;

        $key = $this->factory->fetchForGerritServerId($id);
        $this->assertIsA($key, 'Git_RemoteServer_Gerrit_ReplicationSSHKey');
    }

    public function itThrowsAnExceptionIfKeyDirectoryDoesNotExist() {
        $this->expectException('Git_RemoteServer_Gerrit_ReplicationSSHKeyFactoryException');

        $id = 98;
        $fake_dir = '/over/the/rainbow';
        $git_executor = mock('Git_Exec');
        stub($git_executor)->getPath()->returns($fake_dir);

        $factory = new Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory($git_executor);
        $factory->fetchForGerritServerId($id);
    }

    public function itReturnsAKeyWithNoValueIfFileDoesNotExist() {
        $id = 98;

        $key_dir         = Git_Gitolite_SSHKeyDumper::KEYDIR;
        $key_file_suffix = Git_RemoteServer_Gerrit_ReplicationSSHKey::KEYNAME_SUFFIX;
        $key_prefix      = 'forge__gerrit_';
        $key_file_name = $key_prefix . $id . $key_file_suffix;


        $file = $this->gitolite_directoy . '/'.$key_dir.'/' . $key_file_name;
        $this->assertFalse(is_file($file));

        $key = $this->factory->fetchForGerritServerId($id);
        $this->assertNull($key->getValue());
        $this->assertEqual($id, $key->getGerritHostId());
    }

    public function itReturnsAKeyWithNoValueIfFileIsEmpty() {
        $id = 98;

        $key_dir         = Git_Gitolite_SSHKeyDumper::KEYDIR;
        $key_file_suffix = Git_RemoteServer_Gerrit_ReplicationSSHKey::KEYNAME_SUFFIX;
        $key_prefix      = 'forge__gerrit_';
        $key_file_name = $key_prefix . $id . $key_file_suffix;

        $file = $this->gitolite_directoy . '/'.$key_dir.'/' . $key_file_name;
        $this->assertFalse(is_file($file));

        touch($file);
        $this->assertTrue(is_file($file));
        $file_contents = file_get_contents($file);
        $this->assertEqual($file_contents, null);

        $key = $this->factory->fetchForGerritServerId($id);
        $this->assertNull($key->getValue());
        $this->assertEqual($id, $key->getGerritHostId());
    }

    public function itReturnsAPopulatedKeyIfFileExistsAndHasData() {
        $id = 98;
        $expected_file_contents = 'I am an ssh key@someone';

        $key_ref       = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $key_dir       = Git_Gitolite_SSHKeyDumper::KEYDIR;
        $key_filename  = $key_ref->setGerritHostId($id)->getGitoliteKeyFile();

        $file = $this->gitolite_directoy . '/'.$key_dir.'/' . $key_filename;
        $this->assertFalse(is_file($file));

        $handle = fopen($file, 'x');
        fwrite($handle, $expected_file_contents);
        fclose($handle);

        $this->assertTrue(is_file($file));
        $file_contents = file_get_contents($file);
        $this->assertEqual($expected_file_contents, $file_contents);

        $key = $this->factory->fetchForGerritServerId($id);
        $this->assertEqual($key->getValue(), $expected_file_contents);
        $this->assertEqual($id, $key->getGerritHostId());
    }

    public function tearDown() {
        parent::tearDown();

        $key_dir = Git_Gitolite_SSHKeyDumper::KEYDIR;
        exec('rm -rf ' . $this->gitolite_directoy.'/'.$key_dir);
    }
}

class Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory_DeleteForGerritServerIdTest extends TuleapTestCase {

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

        $this->git_executor = mock('Git_Exec');
        $this->gitolite_directoy = '/var/tmp';
        $key_dir = Git_Gitolite_SSHKeyDumper::KEYDIR;

        if (!is_dir('/var/tmp/'.$key_dir)) {
            exec('mkdir /var/tmp/'.$key_dir);
        }

        stub($this->git_executor)->getPath()->returns($this->gitolite_directoy);
        $this->factory = new Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory($this->git_executor);
    }

    public function itReturnsTrueIfKeyDoesNotExist() {
        $id = 86;

        $key_ref       = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $key_dir       = Git_Gitolite_SSHKeyDumper::KEYDIR;
        $key_filename  = $key_ref->setGerritHostId($id)->getGitoliteKeyFile();

        $file = $this->gitolite_directoy . '/'.$key_dir.'/' . $key_filename;
        $this->assertFalse(is_file($file));

        $result = $this->factory->deleteForGerritServerId($id);
        $this->assertTrue($result);
    }

    public function itRemovesCommitsAndPushesTheDeletion() {
        $id = 86;

        $key_ref       = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $key_dir       = Git_Gitolite_SSHKeyDumper::KEYDIR;
        $key_filename  = $key_ref->setGerritHostId($id)->getGitoliteKeyFile();

        $file = $this->gitolite_directoy . '/'.$key_dir.'/' . $key_filename;
        $this->assertFalse(is_file($file));

        touch($file);

        expect($this->git_executor)->rm(new PatternExpectation('%keydir/forge__gerrit_86@0.pub$%'))->once();
        expect($this->git_executor)->commit()->once();
        expect($this->git_executor)->push()->once();

        $this->factory->deleteForGerritServerId($id);
        unlink($file);
    }
}
?>
