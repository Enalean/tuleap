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

class GitViews_RepoManagement_Pane_NotificationPrefix extends GitViews_RepoManagement_Pane {

    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    public function getIdentifier() {
        return 'mailprefix';
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    public function getTitle() {
        return $GLOBALS['Language']->getText('plugin_git', 'mail_prefix_label');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    public function getContent() {
        $html  = '';
        $hp = Codendi_HTMLPurifier::instance();
        $html .= '<h3>'. $GLOBALS['Language']->getText('plugin_git', 'mail_prefix_title') .'</h3>';
        $html .= '<form id="mail_prefix_form" action="/plugins/git/" method="POST">';
        $html .= '<input type="hidden" id="action" name="action" value="mail_prefix" />';
        $html .= '<input type="hidden" name="pane" value="'. $this->getIdentifier() .'" />';
        $html .= '<input type="hidden" id="group_id" name="group_id" value="'. $this->repository->getProjectId() .'" />';
        $html .= '<input type="hidden" id="repo_id" name="repo_id" value="'. $this->repository->getId() .'" />';
        $html .= '<label for="mail_prefix">'. $GLOBALS['Language']->getText('plugin_git', 'mail_prefix') .'</label>';
        $html .= '<input name="mail_prefix" id="mail_prefix" class="plugin_git_mail_prefix" type="text" value="'. $hp->purify($this->repository->getMailPrefix(), CODENDI_PURIFIER_CONVERT_HTML, $this->repository->getProjectId()) .'" /></td>';
        $html .= '<p>';
        $html .= '<input type="submit" id="mail_prefix_submit" name="mail_prefix_submit" value="'. $GLOBALS['Language']->getText('plugin_git', 'mail_prefix_submit') .'" />';
        $html .= '</p>';
        $html .= '</form>';
        return $html;
    }
}
?>
