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


    const TEMPLATES_PATH = 'gitolite';

    /**
     * @var TemplateRenderer
     */
    private $template_renderer;

    /**
     * @var Git_Mirror_MirrorDataMapper
     */
    private $data_mapper;

    /**
     * @var Git_Driver_Gerrit_ProjectCreatorStatus
     */
    private $gerrit_status;

    private static $permissions_types = array(
        Git::PERM_READ  => ' R  ',
        Git::PERM_WRITE => ' RW ',
        Git::PERM_WPLUS => ' RW+'
    );

    public function __construct(Git_Mirror_MirrorDataMapper $data_mapper, Git_Driver_Gerrit_ProjectCreatorStatus $gerrit_status, $etc_templates_path) {
        $this->data_mapper   = $data_mapper;
        $this->gerrit_status = $gerrit_status;
        $template_dirs = array();
        if (is_dir($etc_templates_path)) {
            $template_dirs[] = $etc_templates_path . '/' . self::TEMPLATES_PATH;
        }
        $template_dirs[] = GIT_TEMPLATE_DIR . '/' . self::TEMPLATES_PATH;
        $this->template_renderer = TemplateRendererFactory::build()->getRenderer($template_dirs);
    }

    public function getGitoliteDotConf(array $project_names) {
        return $this->template_renderer->renderToString(
            'gitolite.conf',
            new Git_Gitolite_Presenter_GitoliteConfPresenter(
                $project_names,
                $this->data_mapper->fetchAll()
            )
        );
    }

    public function getGitoliteDotConfForHostname(array $project_names) {
        return $this->template_renderer->renderToString(
            'gitolite-with-hostname.conf',
            new Git_Gitolite_Presenter_GitoliteConfPresenter(
                $project_names,
                $this->data_mapper->fetchAll()
            )
        );
    }

    public function getAllIncludes(array $project_names) {
        return $this->template_renderer->renderToString(
            'gitolite-includes.conf',
            array(
                "project_names" => $project_names
            )
        );
    }

    public function getAllIncludesForHostname($hostname, array $project_names) {
        return $this->template_renderer->renderToString(
            'gitolite-includes-for-hostname.conf',
            array(
                "hostname"      => $hostname,
                "project_names" => $project_names
            )
        );
    }

    public function getForRepository(GitRepository $repository) {
        $project = $repository->getProject();
        $repo_config = '';
        $repo_config .= $this->fetchConfigPermissions($project, $repository, Git::PERM_READ);
        $repo_config .= $this->formatPermission(Git::PERM_READ, $this->getMirrorUserNames($repository));
        if ($this->isMigrationToGerritCompletedWithSuccess($repository)) {
            $key = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
            $key->setGerritHostId($repository->getRemoteServerId());
            $repo_config .= $this->formatPermission(Git::PERM_WPLUS, array($key->getUserName()));
        } else {
            $repo_config .= $this->fetchConfigPermissions($project, $repository, Git::PERM_WRITE);
            $repo_config .= $this->fetchConfigPermissions($project, $repository, Git::PERM_WPLUS);
        }
        return $repo_config;
    }

    private function isMigrationToGerritCompletedWithSuccess(GitRepository $repository) {
        return $repository->isMigratedToGerrit() &&
               $this->gerrit_status->getStatus($repository) === Git_Driver_Gerrit_ProjectCreatorStatus::DONE;
    }

    private function formatPermission($permission_type, array $granted) {
        if (count($granted)) {
            return self::$permissions_types[$permission_type] . ' = ' . implode(' ', $granted).PHP_EOL;
        }
        return '';
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
        if ($git_online_edit_conf_right) {
            $repository_groups[] = $git_online_edit_conf_right;
        }
        return $this->formatPermission($permission_type, $repository_groups);
    }

    private function getUserForOnlineEdition(GitRepository $repository) {
        if ($repository->hasOnlineEditEnabled()) {
            return 'id_rsa_gl-adm';
        }

        return '';
    }

    private function getMirrorUserNames(GitRepository $repository) {
        $names = array();
        foreach ($this->data_mapper->fetchAllRepositoryMirrors($repository) as $mirror) {
            $names[] = $mirror->owner->getUserName();
        }
        return $names;
    }
}
