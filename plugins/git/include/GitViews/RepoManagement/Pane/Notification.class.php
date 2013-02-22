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

class GitViews_RepoManagement_Pane_Notification extends GitViews_RepoManagement_Pane {

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
        return $GLOBALS['Language']->getText('plugin_git', 'admin_mail');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    public function getContent() {
        $html  = '';
        $html .= '<h3>'. $GLOBALS['Language']->getText('plugin_git', 'admin_mail') .'</h3>';
        $html .= '<form id="mail_prefix_form" action="/plugins/git/" method="POST">';
        $html .= '<input type="hidden" id="action" name="action" value="mail" />';
        $html .= '<input type="hidden" name="pane" value="'. $this->getIdentifier() .'" />';
        $html .= '<input type="hidden" id="group_id" name="group_id" value="'. $this->repository->getProjectId() .'" />';
        $html .= '<input type="hidden" id="repo_id" name="repo_id" value="'. $this->repository->getId() .'" />';
        
        $html .= $this->notificationPrefix();

        $html .= '<h4>'. $GLOBALS['Language']->getText('plugin_git', 'notified_mails_title') .'</h4>';
        $html .= $this->listOfMails();
        $html .= $this->notifiedPeople();
        $html .= '<input type="submit" class="btn" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $html .= '</form>';
        return $html;
    }
    
    private function notificationPrefix() {
        $html  = '';
        $html .= '<label for="mail_prefix">'. $GLOBALS['Language']->getText('plugin_git', 'mail_prefix_label') .'</label>';
        $html .= '<input name="mail_prefix" id="mail_prefix" class="plugin_git_mail_prefix" type="text" value="'. $this->hp->purify($this->repository->getMailPrefix()) .'" />';
        return $html;
    }
    
    private function notifiedPeople() {
        $html  = '';
        $html .= '<label for="add_mail">'. $GLOBALS['Language']->getText('plugin_git', 'add_mail_title') .'</label>';
        $html .= '<textarea id="add_mail" name="add_mail" class="text"></textarea>';
        $html .= '<p class="help-block">'. $GLOBALS['Language']->getText('plugin_git', 'add_mail_msg') .'</p>';
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
            $html .= '<table>';
            $html .= '</thead>';
            $i = 0;
            $html .= '<tbody>';
            foreach ($mails as $mail) {
                $html .= '<tr class="'.html_get_alt_row_color(++$i).'">';
                $html .= '<td>'.$this->hp->purify($mail).'</td>';
                $html .= '<td>';
                $html .= '<label>';
                $html .= '<input type="checkbox" name="remove_mail[]" value="'.$this->hp->purify($mail).'" title="delete" />';
                $html .= '</label>';
                $html .= '</a>';
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '<br />';
        }
        return $html;
    }
}
?>
