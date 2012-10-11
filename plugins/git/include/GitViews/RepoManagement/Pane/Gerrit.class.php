<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class GitViews_RepoManagement_Pane_Gerrit extends GitViews_RepoManagement_Pane {
    
    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    public function getIdentifier() {
        return 'gerrit';
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    public function getTitle() {
        return $GLOBALS['Language']->getText('plugin_git', 'gerrit_settings');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    public function getContent() {
        $html  = '';
        $html .= '<h3>'. $this->getTitle() .'</h3>';
        $html .= '<form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id='. $this->repository->getProjectId() .'">';
        $html .= '<input type="hidden" id="action" name="action" value="migrate_to_gerrit" />';
        $html .= '<input type="hidden" name="pane" value="'. $this->getIdentifier() .'" />';
        $html .= '<input type="hidden" id="repo_id" name="repo_id" value="'. $this->repository->getId() .'" />';

        $html .= '<p>';
        $html .= '<label for="gerrit_url">'. $GLOBALS['Language']->getText('plugin_git', 'gerrit_url') .'</label>';
        $html .= '<input type="text" id="gerrit_url" name="remote_server_id" value="gerrit.tuleap.net">';
        $html .= '</p>';

        $html .= '<p><input type="submit" name="save" value="'. $GLOBALS['Language']->getText('plugin_git', 'gerrit_migrate_to') .'" /></p>';
        $html .= '</form>';
        return $html;
    }
}
?>
