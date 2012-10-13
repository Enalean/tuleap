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

require_once 'Pane.class.php';

class GitViews_RepoManagement_Pane_Delete extends GitViews_RepoManagement_Pane {

    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    public function getIdentifier() {
        return 'delete';
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    public function getTitle() {
        return ucfirst($GLOBALS['Language']->getText('global', 'delete'));
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    public function getContent() {
        $html  = '';
        $html .= '<h3>'. $GLOBALS['Language']->getText('plugin_git', 'admin_deletion_submit') .'</h3>';

        $html .= '<form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id='. $this->repository->getProjectId() .'">';
        $html .= '<input type="hidden" id="repo_id" name="repo_id" value="'. $this->repository->getId() .'" />';

        if ($this->request->get('confirm_deletion')) {
            $html .= $this->fetchConfirmDeletionButton();
        } else {
            $html .= $this->fetchDeleteButton();
        }
        $html .= '</form>';
        return $html;
    }

    private function fetchDeleteButton() {
        $html  = '';
        $html .= '<input type="hidden" id="action" name="action" value="repo_management" />';
        $html .= '<input type="hidden" name="pane" value="'. $this->getIdentifier() .'" />';
        $disabled = '';
        if (!$this->repository->canBeDeleted()) {
            $html .= '<p>'. 'You cannot delete' .'</p>';
            $disabled = 'readonly="readonly" disabled="disabled"';
        }
        $html .= '<input type="submit" name="confirm_deletion" value="'. $GLOBALS['Language']->getText('plugin_git', 'admin_deletion_submit') .'" '. $disabled .'/>';
        return $html;
    }

    private function fetchConfirmDeletionButton() {
        $html  = '';
        $html .= '<div class="alert alert-block">';
        $html .= '<h4>'. $GLOBALS['Language']->getText('global', 'warning!') .'</h4>';
        $html .= '<p>'. $GLOBALS['Language']->getText('plugin_git', 'confirm_deletion_msg', array($this->repository->getFullName())) .'</p>';
        $html .= '<p>';
        $html .= '<input type="hidden" id="action" name="action" value="del" />';
        $html .= '<input type="submit" id="submit" name="submit" value="'. $GLOBALS['Language']->getText('plugin_git', 'yes') .'"/>';
        $onclick = 'onclick="window.location=\'/plugins/git/?'. http_build_query(array(
            'action'   => 'repo_management',
            'pane'     => $this->getIdentifier(),
            'group_id' => $this->repository->getProjectId(),
            'repo_id'  => $this->repository->getId(),
        )) .'\'"';
        $html .= '<input type="button" value="'. $GLOBALS['Language']->getText('plugin_git', 'no') .'" '. $onclick .'/>';
        $html .= '</p>';
        $html .= '</div>';
        return $html;
    }
}
?>
