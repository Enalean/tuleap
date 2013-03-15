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

require_once 'ReplicationSSHKey.class.php';
require_once 'ReplicationSSHKeyFactoryException.class.php';
require_once dirname(__FILE__).'/../../../Git_Gitolite_SSHKeyDumper.class.php';

class Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory {

    const GITOLITE_KEY_DIR   = 'keydir';
    const KEY_FILE_SUFFIX    = '@0.pub';
    const KEY_SAVE_COMMIT_MESSAGE = 'Saving key for Gerrit Server with ID: ';
    const KEY_DELETE_COMMIT_MESSAGE = 'Deleting key for Gerrit Server with ID: ';

    /**
     *
     * @var Git_Exec
     */
    private $git_executer;

    /**
     * @param Git_RemoteServer_Gerrit_ReplicationSSHKey $key
     * @return string
     */
    public static function getReplicationKeyFilenameFromKey(Git_RemoteServer_Gerrit_ReplicationSSHKey $key) {
        return self::getReplicationKeyFilenameForGerritServerId($key->getGerritHostId());
    }

    /**
     * @param int $id
     * @return string
     */
    public static function getReplicationKeyFilenameForGerritServerId($id) {
        return self::getGitoliteUserNameForGerritServerId($id) . self::KEY_FILE_SUFFIX;
    }

    /**
     * @param int $id
     * @return string
     */
    public static function getGitoliteUserNameForGerritServerId($id) {
        return Rule_UserName::RESERVED_PREFIX.'gerrit_'.$id;
    }


    public function __construct(Git_Exec $git_executer) {
        $this->git_executer = $git_executer;
    }

    /**
     *
     * @param int $id
     * @return \Git_RemoteServer_Gerrit_ReplicationSSHKey
     * @throws Git_RemoteServer_Gerrit_ReplicationSSHKeyFactoryException
     */
    public function fetchForGerritServerId($id) {
        $key_dir_path  = $this->getGitoliteKeyDirectory();
        $file_name     = self::getReplicationKeyFilenameForGerritServerId($id);

        $key = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $key->setGerritHostId($id);

        if(! $this->findFileInDirectory($file_name, $key_dir_path)) {
            return $key;
        }

        $file = $key_dir_path . $file_name;
        if (file_get_contents($file)) {
            $key->setValue(file_get_contents($file));
        }

        return $key;
    }

    /**
     * Saves a Replication SSH Key by creating a new file
     * and pushing it to remote git location.
     *
     * @param Git_RemoteServer_Gerrit_ReplicationSSHKey $key
     * @return \Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory
     * @throws Git_RemoteServer_Gerrit_ReplicationSSHKeyFactoryException
     * @throw Git_Command_Exception
     */
    public function save(Git_RemoteServer_Gerrit_ReplicationSSHKey $key) {
        if ($key->getGerritHostId() == null) {
            return;
        }

        if ($key->getValue() ==  null) {
            $this->deleteForGerritServerId($key->getGerritHostId());
            return;
        }

        $key_dumper = new Git_Gitolite_SSHKeyDumper($this->git_executer->getPath(), $this->git_executer, UserManager::instance());
        $key_dumper->dumpSSHKeys($key);

        $this->git_executer->push();

        return $this;
    }

    /**
     *
     * @param int $id
     * @return boolean
     * @throws Git_RemoteServer_Gerrit_ReplicationSSHKeyFactoryException
     * @throw Git_Command_Exception
     */
    public function deleteForGerritServerId($id) {
        $key_dir_path  = $this->getGitoliteKeyDirectory();
        $file_name     = self::getReplicationKeyFilenameForGerritServerId($id);

        if(! $this->findFileInDirectory($file_name, $key_dir_path)) {
            return true;
        }

        $this->git_executer->rm(self::GITOLITE_KEY_DIR.'/'.$file_name);
        $this->git_executer->commit(self::KEY_DELETE_COMMIT_MESSAGE . $id);
        $this->git_executer->push();

        return true;
    }

    /**
     *
     * @return string Path of key directory
     * @throws Git_RemoteServer_Gerrit_ReplicationSSHKeyFactoryException
     */
    private function getGitoliteKeyDirectory() {
        $key_dir_path  = $this->git_executer->getPath(). '/'.self::GITOLITE_KEY_DIR.'/';
        if (! is_dir($key_dir_path)) {
            throw new Git_RemoteServer_Gerrit_ReplicationSSHKeyFactoryException('gitolite admin key directory does not exist');
        }

        return $key_dir_path;
    }

    /**
     *
     * @param string $filename
     * @param string $directory
     * @return string | false
     */
    private function findFileInDirectory($filename, $directory) {
        $directory_handle  = opendir($directory);
        while (false !== ($file = readdir($directory_handle))) {
            if (preg_match('|'.$filename.'|', $file)) {
                break;
            }
        }

        return $file;
    }
}
?>
