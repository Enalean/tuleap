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

class Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory {

    const GOTOLITE_KEY_DIR   = 'keydir';
    const KEY_FILE_SUFFIX    = '@0.pub';
    const KEY_COMMIT_MESSAGE = 'Saving key for Gerrit Server with ID: ';

    /**
     *
     * @var Git_Exec
     */
    private $git_executer;

    /**
     *
     * @param int $id
     * @return string
     */
    public static function getReplicationKeyFilenameForGerritServerId($id) {
        $key = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $key->setGerritHostId($id);

        $file_name = $key->getUserName() . self::KEY_FILE_SUFFIX;
        return $file_name;
    }


    public function __construct(Git_Exec $git_executer) {
        $this->git_executer = $git_executer;
    }

    /**
     *
     * @param int $id
     * @return \Git_RemoteServer_Gerrit_ReplicationSSHKey
     */
    public function fetchForGerritServerId($id) {
        $key = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $key->setGerritHostId($id);

        $key_dir_path  = $this->getGitoliteKeyDirectory();
        $expected_file_name = self::getReplicationKeyFilenameForGerritServerId($id);

        $directory_handle  = opendir($key_dir_path);
        while (false !== ($file_name = readdir($directory_handle))) {
            if (preg_match('|'.$expected_file_name.'|', $file_name)) {
                break;
            }
        }

        if(! $file_name) {
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
     */
    public function save(Git_RemoteServer_Gerrit_ReplicationSSHKey $key) {
        if ($key->getGerritHostId() == null || $key->getUserName() == null || $key->getValue() == null) {
            return;
        }

        $key_dir_path  = $this->getGitoliteKeyDirectory();
        $key_file_name = self::getReplicationKeyFilenameForGerritServerId($key->getGerritHostId());
        $key_path = $key_dir_path . $key_file_name;

        $this->saveKeyInFileSystem($key, $key_path);

        $this->git_executer->add($key_path);
        $this->git_executer->commit(self::KEY_COMMIT_MESSAGE . $key->getGerritHostId());
        $this->git_executer->push();

        return $this;
    }

    private function saveKeyInFileSystem($key, $key_path) {
        $handle = fopen($key_path, 'wx+');
        fwrite($handle, $key->getValue());
        fclose($handle);
    }

    /**
     *
     * @return string Path of key directory
     * @throws Git_RemoteServer_Gerrit_ReplicationSSHKeyFactoryException
     */
    private function getGitoliteKeyDirectory() {
        $key_dir_path  = $this->git_executer->getPath(). '/'.self::GOTOLITE_KEY_DIR.'/';
        if (! is_dir($key_dir_path)) {
            throw new Git_RemoteServer_Gerrit_ReplicationSSHKeyFactoryException('gitolite admin key directory does not exist');
        }

        return $key_dir_path;
    }
}
?>
