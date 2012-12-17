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
     * Display access control management for gitolite backend
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