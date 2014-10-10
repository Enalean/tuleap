<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

class Git_Gitolite_ConfigPermissionsSerializer {

    private static $permissions_types = array(
        Git::PERM_READ  => ' R  ',
        Git::PERM_WRITE => ' RW ',
        Git::PERM_WPLUS => ' RW+'
    );

    public function getForRepository(GitRepository $repository) {
        $project = $repository->getProject();
        $repo_config = '';
        $repo_config .= $this->fetchConfigPermissions($project, $repository, Git::PERM_READ);

        if ($repository->isMigratedToGerrit()) {
            $key = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
            $key->setGerritHostId($repository->getRemoteServerId());
            $repo_config .= self::$permissions_types[Git::PERM_WPLUS] . ' = ' .$key->getUserName() . PHP_EOL;
        } else {
            $repo_config .= $this->fetchConfigPermissions($project, $repository, Git::PERM_WRITE);
            $repo_config .= $this->fetchConfigPermissions($project, $repository, Git::PERM_WPLUS);
        }
        return $repo_config;
    }

    /**
     * Fetch the gitolite readable conf for permissions on a repository
     *
     * @return string
     */
    public function fetchConfigPermissions($project, $repository, $permission_type) {
        if (!isset(self::$permissions_types[$permission_type])) {
            return '';
        }
        $git_online_edit_conf_right = $this->getUserForOnlineEdition($repository);
        $ugroup_literalizer = new UGroupLiteralizer();
        $repository_groups  = $ugroup_literalizer->getUGroupsThatHaveGivenPermissionOnObject($project, $repository->getId(), $permission_type);
        if (count($repository_groups) == 0) {
            return '';
        }
        return self::$permissions_types[$permission_type] . ' = ' . implode(' ', $repository_groups).$git_online_edit_conf_right . PHP_EOL;
    }

    private function getUserForOnlineEdition(GitRepository $repository) {
        if ($repository->hasOnlineEditEnabled()) {
            return ' id_rsa_gl-adm';
        }

        return '';
    }
}
