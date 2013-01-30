<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

/**
 * GitForkPermissionsManager
 */
class GitForkPermissionsManager {

    public function __construct($repository) {
        $this->repository  = $repository;
    }

    /**
     * Wrapper
     *
     * @return ProjectManager
     */
    function getProjectManager() {
        return $this->repository->_getProjectManager();
    }

    /**
     * Wrapper
     *
     * @return Codendi_HTMLPurifier
     */
    function getPurifier() {
        return Codendi_HTMLPurifier::instance();
    }

    /**
     * Prepare fork destination message according to the fork scope
     *
     * @param Array $params Request params
     *
     * @return String
     */
    private function displayForkDestinationMessage($params) {
        if ($params['scope'] == 'project') {
            $project         = $this->getProjectManager()->getProject($params['group_id']);
            $destinationHTML = $GLOBALS['Language']->getText('plugin_git', 'fork_destination_project_message',  array($project->getPublicName()));
        } else {
            $destinationHTML = $GLOBALS['Language']->getText('plugin_git', 'fork_destination_personal_message');
        }
        return $destinationHTML;
    }

    /**
     * Prepare fork repository message
     *
     * @param String $repos Comma separated repositories Ids selected for fork
     *
     * @return String
     */
    private function displayForkSourceRepositories($repos) {
        $dao             = new GitDao();
        $repoFactory     = new GitRepositoryFactory($dao, $this->getProjectManager());
        $sourceReposHTML = '';
        $repositories    = explode(',', $repos);

        foreach ($repositories as $repositoryId) {
            $repository       = $repoFactory->getRepositoryById($repositoryId);
            $sourceReposHTML .= '"'.$this->getPurifier()->purify($repository->getFullName()).'" ';
        }
        return $sourceReposHTML;
    }

    /**
     * Fetch the html code to display permissions form when forking repositories
     *
     * @param Array   $params   Request params
     * @param Integer $groupId  Project Id
     * @param String  $userName User name
     *
     * @return String
     */
    public function displayRepositoriesPermissionsForm($params, $groupId, $userName) {
        $sourceReposHTML = $this->displayForkSourceRepositories($params['repos']);
        $form  = '<h2>'.$GLOBALS['Language']->getText('plugin_git', 'fork_repositories').'</h2>';
        $form .= $GLOBALS['Language']->getText('plugin_git', 'fork_repository_message', array($sourceReposHTML));
        $form .= $this->displayForkDestinationMessage($params);
        $form .= '<h3>Set permissions for the repository to be created</h3>';
        $form .= '<form action="" method="POST">';
        $form .= '<input type="hidden" name="group_id" value="'.(int)$groupId.'" />';
        $form .= '<input type="hidden" name="action" value="do_fork_repositories" />';
        $token = new CSRFSynchronizerToken('/plugins/git/?group_id='.(int)$groupId.'&action=fork_repositories');
        $form .= $token->fetchHTMLInput();
        $form .= '<input id="fork_repositories_repo" type="hidden" name="repos" value="'.$this->getPurifier()->purify($params['repos']).'" />';
        $form .= '<input id="choose_personal" type="hidden" name="choose_destination" value="'.$this->getPurifier()->purify($params['scope']).'" />';
        $form .= '<input id="to_project" type="hidden" name="to_project" value="'.$this->getPurifier()->purify($params['group_id']).'" />';
        $form .= '<input type="hidden" id="fork_repositories_path" name="path" value="'.$this->getPurifier()->purify($params['namespace']).'" />';
        $form .= '<input type="hidden" id="fork_repositories_prefix" value="u/'. $userName .'" />';
        $form .= $this->displayAccessControl($groupId);
        $form .= '<input type="submit" value="'.$GLOBALS['Language']->getText('plugin_git', 'fork_repositories').'" />';
        $form .= '</form>';
        return $form;
    }

    /**
     * Display access control management for gitolite backend
     *
     * @param Integer $projectId Project Id, to manage permissions when performing a cross project fork
     *
     * @return String
     */
    public function displayAccessControl($projectId = NULL) {
        $html  = '';
        $disabled = $this->repository->getRemoteServerId() ? true : false;
        if ($disabled) {
            $html .= '<div class="alert-message block-message warning">';
            $html .=  $GLOBALS['Language']->getText('plugin_git', 'permissions_on_remote_server');
            $html .= '</div>';
        }
        if (empty($projectId)) {
            $projectId = $this->repository->getProjectId();
        }
        $html  = '<table>';
        $html .= '<thead><tr>';
        $html .= '<td>'. $GLOBALS['Language']->getText('plugin_git', 'perm_R') .'</td>';
        $html .= '<td>'. $GLOBALS['Language']->getText('plugin_git', 'perm_W') .'</td>';
        $html .= '<td>'. $GLOBALS['Language']->getText('plugin_git', 'perm_W+') .'</td>';
        $html .= '</tr></thead>';
        $html .= '<tbody><tr>';
        // R
        $html .= '<td>';
        $html .= permission_fetch_selection_field('PLUGIN_GIT_READ', $this->repository->getId(), $projectId, 'repo_access[read]', $disabled);
        $html .= '</td>';
        // W
        $html .= '<td>';
        $html .= permission_fetch_selection_field('PLUGIN_GIT_WRITE', $this->repository->getId(), $projectId, 'repo_access[write]', $disabled);
        $html .= '</td>';
        // W+
        $html .= '<td>';
        $html .= permission_fetch_selection_field('PLUGIN_GIT_WPLUS', $this->repository->getId(), $projectId, 'repo_access[wplus]', $disabled);
        $html .= '</td>';
        $html .= '</tr></tbody>';
        $html .= '</table>';
        return $html;
    }

}

?>