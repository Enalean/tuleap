<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/
 */

require_once('GitRepository.class.php');

/**
 * Dedicated screen for repo management
 */
class GitViews_RepoManagement {

    /**
     * @var GitRepository
     */
    private $repository;

    /**
     * @var GitViews
     */
    private $parent_view;

    /**
     * @var Codendi_Request
     */
    private $request;

    public function __construct(GitViews $parent_view, GitRepository $repository, Codendi_Request $request) {
        $this->parent_view  = $parent_view;
        $this->repository   = $repository;
        $this->request      = $request;
        $this->current_pane = 'settings';

        $panes = array('settings', 'perms', 'mailprefix', 'mail', 'delete');
        if (in_array($request->get('pane'), $panes)) {
            $this->current_pane = $request->get('pane');
        }
    }

    /**
     * Output repo management sub screen to the browser
     */
    public function display() {
        echo '<div class="tabbable tabs-left">';
        echo '<ul class="nav nav-tabs">';
        echo '<li class="'. ($this->current_pane == 'settings' ? 'active' : '') .'"><a href="/plugins/git/?action=repo_management&group_id=102&repo_id=50&pane=settings">'. 'General Settings' .'</a></li>';
        echo '<li class="'. ($this->current_pane == 'perms' ? 'active' : '') .'"><a href="/plugins/git/?action=repo_management&group_id=102&repo_id=50&pane=perms">'. 'Access Control' .'</a></li>';
        echo '<li class="'. ($this->current_pane == 'mailprefix' ? 'active' : '') .'"><a href="/plugins/git/?action=repo_management&group_id=102&repo_id=50&pane=mailprefix">'. 'Notification prefix' .'</a></li>';
        echo '<li class="'. ($this->current_pane == 'mail' ? 'active' : '') .'"><a href="/plugins/git/?action=repo_management&group_id=102&repo_id=50&pane=mail">'. 'Notificated people' .'</a></li>';
        echo '<li class="'. ($this->current_pane == 'delete' ? 'active' : '') .'"><a href="/plugins/git/?action=repo_management&group_id=102&repo_id=50&pane=delete">'. 'Delete' .'</a></li>';
        echo '</ul>';
        echo '<div id="git_repomanagement" class="tab-content">';
        $this->current_pane == 'settings' ? $this->descriptionForm()    : '';
        $this->current_pane == 'perms' ? $this->accessControlForm()     : '';
        $this->current_pane == 'mailprefix' ? $this->mailPrefixForm()   : '';
        $this->current_pane == 'mail' ? $this->addMailForm()            : '';
        $this->current_pane == 'delete' ? $this->deleteForm()           : '';
        echo '</div>';
        echo '</div>';
    }

    private function deleteForm() {
        echo '<div id="git_repomanagement_delete" class="tab-pane active">';
        echo '<h3>'. 'Delete' .'</h3>';
        echo '<form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id='. $this->repository->getProjectId() .'">';
        echo '<input type="hidden" id="action" name="action" value="edit" />';
        echo '<input type="hidden" id="repo_id" name="repo_id" value="'. $this->repository->getId() .'" />';
        $disabled = '';
        if ($this->repository->hasChild()) {
            echo '<p>'. 'You cannot delete' .'</p>';
            $disabled = 'readonly="readonly" disabled="disabled"';
        }
        echo '<input type="submit" name="confirm_deletion" value="'. $this->getText('admin_deletion_submit') .'" '. $disabled .'/>';
        echo '</form>';
        echo '</div>';
    }

    private function descriptionForm() {
        $hp = Codendi_HTMLPurifier::instance();
        echo '<div id="git_repomanagement_settings" class="tab-pane active">';
        echo '<h3>'. 'General Settings' .'</h3>';
        echo '<form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id='. $this->repository->getProjectId() .'">';
        echo '<input type="hidden" id="action" name="action" value="edit" />';
        echo '<input type="hidden" id="repo_id" name="repo_id" value="'. $this->repository->getId() .'" />';

        echo '<p>';
        echo '<label for="repo_desc">'. $this->getText('view_repo_description') .':</label>';
        echo '<textarea class="text" id="repo_desc" name="repo_desc">';
        echo $hp->purify($this->repository->getDescription(), CODENDI_PURIFIER_CONVERT_HTML, $this->repository->getProjectId());
        echo '</textarea>';
        echo '</p>';

        echo '<p><input type="submit" name="save" value="'. $this->getText('admin_save_submit') .'" /></p>';
        echo '</form>';
        echo '</div>';
    }

    private function accessControlForm() {
        echo '<div id="git_repomanagement_perms" class="tab-pane active">';
        echo '<h3>'. 'Access Control' .'</h3>';
        echo '<form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id='. $this->repository->getProjectId() .'">';
        echo '<input type="hidden" id="action" name="action" value="edit" />';
        echo '<input type="hidden" id="repo_id" name="repo_id" value="'. $this->repository->getId() .'" />';
        if ($this->repository->getBackend() instanceof Git_Backend_Gitolite) {
            $this->accessControlGitolite();
        } else {
            $this->accessControl();
        }
        echo '<p><input type="submit" name="save" value="'. $this->getText('admin_save_submit') .'" /></p>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Display access control management for gitshell backend
     *
     * @return void
     */
    private function accessControl() {
        $public  = '';
        $private = '';
        $checked = 'checked="checked"';
        if ( $this->repository->getAccess() == GitRepository::PRIVATE_ACCESS ) {
            $private = $checked;
            echo '<input type="hidden" id="action" name="action" value="edit" />';
        } else if ( $this->repository->getAccess() == GitRepository::PUBLIC_ACCESS ) {
            $public  = $checked;
            echo '<input type="hidden" id="action" name="action" value="confirm_private" />';
        }
        echo '<p id="plugin_git_access">';
        echo $this->getText('view_repo_access');
        echo ': <span><input type="radio" name="repo_access" value="private" '. $private .'/> ';
        echo $this->getText('view_repo_access_private');
        echo '<input type="radio" name="repo_access" value="public" '. $public .'/> Public';
        echo '</span>';
        echo '</p>';

    }

    /**
     * Display access control management for gitolite backend
     *
     * @return void
     */
    private function accessControlGitolite() {
        echo '<table>';
        echo '<thead><tr>';
        echo '<td>'. $this->getText('perm_R') .'</td>';
        echo '<td>'. $this->getText('perm_W') .'</td>';
        echo '<td>'. $this->getText('perm_W+') .'</td>';
        echo '</tr></thead>';
        echo '<tbody><tr>';
        // R
        echo '<td>';
        echo permission_fetch_selection_field('PLUGIN_GIT_READ', $this->repository->getId(), $this->repository->getProjectId(), 'repo_access[read]');
        echo '</td>';
        // W
        echo '<td>';
        echo permission_fetch_selection_field('PLUGIN_GIT_WRITE', $this->repository->getId(), $this->repository->getProjectId(), 'repo_access[write]');
        echo '</td>';
        // W+
        echo '<td>';
        echo permission_fetch_selection_field('PLUGIN_GIT_WPLUS', $this->repository->getId(), $this->repository->getProjectId(), 'repo_access[wplus]');
        echo '</td>';

        echo '</tr></tbody>';
        echo '</table>';
    }

    /**
     * form to update notification mail prefix
     */
    private function mailPrefixForm() {
        $hp = Codendi_HTMLPurifier::instance();
        echo '<div id="git_repomanagement_mailprefix" class="tab-pane active">';
        ?>
<h3><?php echo $this->getText('mail_prefix_title'); ?></h3>
<form id="mail_prefix_form" action="/plugins/git/" method="POST">
    <input type="hidden" id="action" name="action" value="mail_prefix" />
    <input type="hidden" id="group_id" name="group_id" value="<?php echo $this->repository->getProjectId() ?>" />
    <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $this->repository->getId() ?>" />
    <label for="mail_prefix"><?= $this->getText('mail_prefix'); ?></label>
    <input name="mail_prefix" id="mail_prefix" class="plugin_git_mail_prefix" type="text" value="<?= $hp->purify($this->repository->getMailPrefix(), CODENDI_PURIFIER_CONVERT_HTML, $this->repository->getProjectId()); ?>" /></td>
    <p>
        <input type="submit" id="mail_prefix_submit" name="mail_prefix_submit" value="<?= $this->getText('mail_prefix_submit')?>">
    </p>
</form>
        <?php
        echo '</div>';
    }

    /**
     * form to add email addresses (mailing list) or a user to notify
     */
    private function addMailForm() {
        echo '<div id="git_repomanagement_mail" class="tab-pane active">';
        $this->listOfMails();
        ?>
<h3><?php echo $this->getText('add_mail_title'); ?></h3>
<form id="add_mail_form" action="/plugins/git/" method="POST">
    <input type="hidden" id="action" name="action" value="add_mail" />
    <input type="hidden" id="group_id" name="group_id" value="<?php echo $this->repository->getProjectId() ?>" />
    <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $this->repository->getId() ?>" />
    <label for="add_mail"><?php echo $this->getText('add_mail');?></label>
    <textarea id="add_mail" name="add_mail" class="plugin_git_add_mail"></textarea>
    <p class="help-block"><?= $this->getText('add_mail_msg') ?></p>
    <input type="submit" id="add_mail_submit" name="add_mail_submit" value="<?php echo $this->getText('add_mail_submit')?>" />
</form>
        <?php
        echo '</div>';
        $this->parent_view->help('addMail', array('display'=>'none') );
        $js = "new UserAutoCompleter('add_mail', '".util_get_dir_image_theme()."', true);";
        $GLOBALS['Response']->includeFooterJavascriptSnippet($js);
    }

    /**
     * show the list of mails to notify
     */
    private function listOfMails() {
        $hp = Codendi_HTMLPurifier::instance();
        $mails = $this->repository->getNotifiedMails();
        if ($mails) {
        ?>
<h3><?php echo $this->getText('notified_mails_title'); ?></h3>
    <?php ?>
<form id="add_user_form" action="/plugins/git/" method="POST">
    <input type="hidden" id="action" name="action" value="remove_mail" />
    <input type="hidden" id="group_id" name="group_id" value="<?php echo $this->repository->getProjectId() ?>" />
    <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $this->repository->getId() ?>" />
    <table>
        <?php
        $i = 0;
        foreach ($mails as $mail) {
            echo '<tr class="'.html_get_alt_row_color(++$i).'">';
            echo '<td>'.$mail.'</td>';
            echo '<td>';
            echo '<input type="checkbox" name="mail[]" value="'.$hp->purify($mail).'" />';
            echo '</a>';
            echo '</td>';
            echo '</tr>';
        }
        ?>
    </table>
    <input type="submit" value="<?php echo $GLOBALS['Language']->getText('global', 'btn_delete') ?>" />
</form>
        <?php
        }
    }

    private function getText($key, $params=array() ) {
        return $GLOBALS['Language']->getText('plugin_git', $key, $params);
    }
}
?>
