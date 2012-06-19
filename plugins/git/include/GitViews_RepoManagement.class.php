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

    public function __construct(GitViews $parent_view, GitRepository $repository) {
        $this->parent_view = $parent_view;
        $this->repository  = $repository;
    }

    /**
     * Output repo management sub screen to the browser
     */
    public function display() {
        echo '<div id="git_repomanagement">';
        ?>
        <form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id=<?= $this->repository->getProjectId() ?>">
        <input type="hidden" id="action" name="action" value="edit" />
        <input type="hidden" id="repo_id" name="repo_id" value="<?= $this->repository->getId() ?>" />
        <?php
        $this->deleteForm();
        $this->descriptionForm();
        echo '</form>';
        // form to update notification mail prefix
        $this->mailPrefixForm();
        // form to add email addresses (mailing list) or a user to notify
        $this->addMailForm();
        // show the list of mails to notify
        $this->listOfMails();

        echo '</div>';
    }

    private function deleteForm() {
        if (!$this->repository->hasChild()) {
            echo '<div id="plugin_git_confirm_deletion"><input type="submit" name="confirm_deletion" value="'. $this->getText('admin_deletion_submit') .'" /></div>';
        }
    }

    private function descriptionForm() {
        $hp = Codendi_HTMLPurifier::instance();
        echo '<form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id='. $this->repository->getProjectId() .'">';
        echo '<input type="hidden" id="repo_id" name="repo_id" value="'. $this->repository->getId() .'" />';

        echo '<p id="plugin_git_description">';
        echo $this->getText('view_repo_description') .': ';
        echo '<textarea class="text" id="repo_desc" name="repo_desc">';
        echo $hp->purify($this->repository->getDescription(), CODENDI_PURIFIER_CONVERT_HTML, $this->repository->getProjectId());
        echo '</textarea>';
        echo '</p>';

        if ($this->repository->getBackend() instanceof Git_Backend_Gitolite) {
            $this->accessControlGitolite();
        } else {
            $this->accessControl();
        }

        echo '<p><input type="submit" name="save" value="'. $this->getText('admin_save_submit') .'" /></p>';
        echo '</form>';
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
     * CONFIRM_DELETION
     * @todo make a generic function ?
     * @param <type> $params
     * @return <type>
     */
    private function confirm_deletion( $params ) {
        if (  empty($params['repo_id']) ) {
            return false;
        }
        $repoId = $params['repo_id'];
        if ( !$this->getController()->isAPermittedAction('del') ) {
            return false;
        }
        ?>
    <div class="confirm">
        <form id="confirm_deletion" method="POST" action="/plugins/git/?group_id=<?php echo $this->repository->getProjectId(); ?>" >
        <input type="hidden" id="action" name="action" value="del" />
        <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $repoId; ?>" />
        <input type="submit" id="submit" name="submit" value="<?php echo $this->getText('yes') ?>"/><span><input type="button" value="<?php echo $this->getText('no')?>" onclick="window.location='/plugins/git/?action=view&group_id=<?php echo $this->repository->getProjectId();?>&repo_id=<?php echo $repoId?>'"/> </span>
        </form>
    </div>
        <?php
    }

    /**
     * CREATE NOTIFICATION FORM
     */
    private function mailPrefixForm() {
        $hp = Codendi_HTMLPurifier::instance();
        ?>
<h3><?php echo $this->getText('mail_prefix_title'); ?></h3>
<form id="mail_prefix_form" action="/plugins/git/" method="POST">
    <input type="hidden" id="action" name="action" value="mail_prefix" />
    <input type="hidden" id="group_id" name="group_id" value="<?php echo $this->repository->getProjectId() ?>" />
    <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $this->repository->getId() ?>" />
    <table>
        <tr>
            <td class="plugin_git_first_col" ><label for="mail_prefix_label"><?php echo $this->getText('mail_prefix');
        ?></label></td>
            <td><input name="mail_prefix" class="plugin_git_mail_prefix" type="text" value="<?= $hp->purify($this->repository->getMailPrefix(), CODENDI_PURIFIER_CONVERT_HTML, $this->repository->getProjectId()); ?>" /></td>
        </tr>
        <tr>
            <td colspan="2"><input type="submit" id="mail_prefix_submit" name="mail_prefix_submit" value="<?php echo $this->getText('mail_prefix_submit')?>"></td>
        </tr>
    </table>
</form>
        <?php
    }

    /**
     * MAIL FORM
     */
    private function addMailForm() {
        ?>
<h3><?php echo $this->getText('add_mail_title'); ?></h3>
<form id="add_mail_form" action="/plugins/git/" method="POST">
    <input type="hidden" id="action" name="action" value="add_mail" />
    <input type="hidden" id="group_id" name="group_id" value="<?php echo $this->repository->getProjectId() ?>" />
    <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $this->repository->getId() ?>" />
    <table>
        <tr>
            <td class="plugin_git_first_col" ><label for="add_mail_label"><?php echo $this->getText('add_mail');?>
                <a href="#" onclick="$('help_addMail').toggle();"> [?]</a></label></td>
            <td><textarea id="add_mail" name="add_mail" class="plugin_git_add_mail"></textarea></td>
        </tr>
        <tr>
            <td colspan="2"><input type="submit" id="add_mail_submit" name="add_mail_submit" value="<?php echo $this->getText('add_mail_submit')?>"></td>
        </tr>
    </table>
</form>
        <?php
        $this->parent_view->help('addMail', array('display'=>'none') );
        $js = "new UserAutoCompleter('add_mail', '".util_get_dir_image_theme()."', true);";
        $GLOBALS['Response']->includeFooterJavascriptSnippet($js);
    }

    /**
     * LIST OF MAILS TO NOTIFY
     */
    private function listOfMails() {
        $r = new GitRepository();
        $r->setId($this->repository->getId());
        $r->load();
        $mails = $r->getNotifiedMails();
        ?>
<h3><?php echo $this->getText('notified_mails_title'); ?></h3>
    <?php if (!empty($mails)) {?>
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
            echo '<input type="checkbox" name="mail[]" value="'.$this->HTMLPurifier->purify($mail).'" />';
            echo '</a>';
            echo '</td>';
            echo '</tr>';
        }
        ?>
    </table>
    <input type="submit" value="<?php echo $GLOBALS['Language']->getText('global', 'btn_delete') ?>" />
</form>
        <?php
        } else {
?>
<h4><?php echo $this->getText('add_mail_existing'); ?> </h4>
<?php
}
    }

    private function getText($key, $params=array() ) {
        return $GLOBALS['Language']->getText('plugin_git', $key, $params);
    }
}
?>
