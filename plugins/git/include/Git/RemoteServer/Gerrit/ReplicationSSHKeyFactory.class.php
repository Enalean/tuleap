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
class Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory {

    /**
     *
     * @var Git_Exec
     */
    private $git_executer;

    public function __construct(Git_Exec $git_executer) {
        $this->git_executer = $git_executer;
    }

    /**
     *
     * @param int $id
     * @return \Git_RemoteServer_Gerrit_ReplicationSSHKey
     */
    public function fetchForGerritServerId($id) {
        return new Git_RemoteServer_Gerrit_ReplicationSSHKey();
    }


    public function save(Git_RemoteServer_Gerrit_ReplicationSSHKey $key) {
        
        
        if ($key->getGerritHostId() == null || $key->getUserName() == null || $key->getValue() == null) {
            return;
        }

        $key_dir_path  = $this->git_executer->getPath() . '/key/';

        if (!is_dir($key_dir_path)) {
            throw new Exception('gitolite admin key directoy does not exist');
        }

        $key_file_name = $key->getUserName() . '.pub';
        $key_path = $key_dir_path . $key_file_name;

        $handle = fopen($key_path, 'x');
        fwrite($handle, $key->getValue());
        fclose($handle);

        $this->git_executer->add('$file');

        return $this;
    }
}
?>
