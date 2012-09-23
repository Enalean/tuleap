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

class GitViews_RepoManagement_Pane_NotificatedPeople extends GitViews_RepoManagement_Pane {

    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    public function getIdentifier() {
        return 'mail';
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    public function getTitle() {
        return $GLOBALS['Language']->getText('plugin_git', 'add_mail_label');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    public function getContent() {
        $html  = '';
        $html .= $this->listOfMails();
        $html .= '<h3>'. $GLOBALS['Language']->getText('plugin_git', 'add_mail_title') .'</h3>';
        $html .= '<form id="add_mail_form" action="/plugins/git/" method="POST">';
        $html .= '<input type="hidden" id="action" name="action" value="add_mail" />';
        $html .= '<input type="hidden" name="pane" value="'. $this->getIdentifier() .'" />';
        $html .= '<input type="hidden" id="group_id" name="group_id" value="'. $this->repository->getProjectId() .'" />';
        $html .= '<input type="hidden" id="repo_id" name="repo_id" value="'. $this->repository->getId() .'" />';
        $html .= '<label for="add_mail">'. $GLOBALS['Language']->getText('plugin_git', 'add_mail') .'</label>';
        $html .= '<textarea id="add_mail" name="add_mail" class="plugin_git_add_mail"></textarea>';
        $html .= '<p class="help-block">'. $GLOBALS['Language']->getText('plugin_git', 'add_mail_msg') .'</p>';
        $html .= '<input type="submit" id="add_mail_submit" name="add_mail_submit" value="'. $GLOBALS['Language']->getText('plugin_git', 'add_mail_submit').'" />';
        $html .= '</form>';
        $js = "new UserAutoCompleter('add_mail', '".util_get_dir_image_theme()."', true);";
        $GLOBALS['Response']->includeFooterJavascriptSnippet($js);
        return $html;
    }

    /**
     * show the list of mails to notify
     */
    private function listOfMails() {
        $html  = '';
        $mails = $this->repository->getNotifiedMails();
        if ($mails) {
            $html .= '<h3>'. $GLOBALS['Language']->getText('plugin_git', 'notified_mails_title') .'</h3>';
            $html .= '<form id="add_user_form" action="/plugins/git/" method="POST">';
            $html .= '<input type="hidden" id="action" name="action" value="remove_mail" />';
            $html .= '<input type="hidden" name="pane" value="'. $this->getIdentifier() .'" />';
            $html .= '<input type="hidden" id="group_id" name="group_id" value="'. $this->repository->getProjectId() .'" />';
            $html .= '<input type="hidden" id="repo_id" name="repo_id" value="'. $this->repository->getId() .'" />';
            $html .= '<table>';
            $i = 0;
            foreach ($mails as $mail) {
                $html .= '<tr class="'.html_get_alt_row_color(++$i).'">';
                $html .= '<td>'.$this->hp->purify($mail).'</td>';
                $html .= '<td>';
                $html .= '<input type="checkbox" name="mail[]" value="'.$this->hp->purify($mail).'" />';
                $html .= '</a>';
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
            $html .= '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_delete') .'" />';
            $html .= '</form>';
        }
        return $html;
    }
}
?>
