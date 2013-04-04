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
     * @var array
     */
    private $gerrit_servers;

    /** @var Git_Driver_Gerrit */
    private $driver;

    public function __construct(GitRepository $repository, Codendi_Request $request, Git_Driver_Gerrit $driver, array $gerrit_servers) {
        parent::__construct($repository, $request);
        $this->gerrit_servers = $gerrit_servers;
        $this->driver         = $driver;
    }

    /**
     * @return bool true if the pane can be displayed
     */
    public function canBeDisplayed() {
        return (Config::get('sys_auth_type') === Config::AUTH_TYPE_LDAP &&
                count($this->gerrit_servers) > 0);
    }

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
        return $GLOBALS['Language']->getText('plugin_git', 'gerrit_pane_title');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    public function getContent() {
        if ($this->repository->getRemoteServerId()) {
            return $this->getContentAlreadyMigrated();
        } else if (! $this->repository->canMigrateToGerrit()) {
            return $this->getContentCannotMigrate();
        }

        $html  = '';
        $html .= '<h3>'. $GLOBALS['Language']->getText('plugin_git', 'gerrit_title') .'</h3>';
        $html .= '<form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id='. $this->repository->getProjectId() .'">';
        $html .= '<input type="hidden" id="action" name="action" value="migrate_to_gerrit" />';
        $html .= '<input type="hidden" name="pane" value="'. $this->getIdentifier() .'" />';
        $html .= '<input type="hidden" id="repo_id" name="repo_id" value="'. $this->repository->getId() .'" />';

        $html .= '<p>';
        $html .= $GLOBALS['Language']->getText('plugin_git', 'gerrit_migration_description', $this->repository->getName());
        $html .= '</p>';
        $html .= '<div class="git_repomanagement_gerrit_more_description">';
        $html .= $GLOBALS['Language']->getText('plugin_git', 'gerrit_migration_more_description', $this->driver->getGerritProjectName($this->repository));
        $html .= '</div>';
        $html .= '<p>';
        $html .= '<label for="gerrit_url">'. $GLOBALS['Language']->getText('plugin_git', 'gerrit_url') .'</label>';
        $html .= '<select name="remote_server_id" id="gerrit_url">';
        $html .= '<option value="">'. $GLOBALS['Language']->getText('global', 'please_choose_dashed') .'</option>';
        foreach ($this->gerrit_servers as $server) {
            $html .= '<option value="'. (int)$server->getId() .'">'. $this->hp->purify($server->getHost()) .'</option>';
        }
        $html .= '</select>';
        $html .= '</p>';

        $html .= '<p><input type="submit" name="save" value="'. $GLOBALS['Language']->getText('plugin_git', 'gerrit_migrate_to') .'" /></p>';
        $html .= '</form>';
        return $html;
    }

    private function getContentCannotMigrate() {
        $html  = '';
        $html .= '<h3>'. $GLOBALS['Language']->getText('plugin_git', 'gerrit_title') .'</h3>';
        $html .= '<p class="alert alert-block">'.  $GLOBALS['Language']->getText('plugin_git', 'gerrit_cannot_migrate_error_msg') .'</p>';
        return $html;
    }

    private function getContentAlreadyMigrated() {
        $btn_name      = 'confirm_disconnect_gerrit';
        if ($this->request->get($btn_name)) {
            return $this->getDisconnectFromGerritConfirmationScreen();
        }

        $gerrit_project = $this->driver->getGerritProjectName($this->repository);
        $gerrit_server  = $this->gerrit_servers[$this->repository->getRemoteServerId()];
        $link           = $gerrit_server->getProjectAdminUrl($gerrit_project);

        $html  = '';
        $html .= '<h3>'. $GLOBALS['Language']->getText('plugin_git', 'gerrit_title') .'</h3>';
        $html .= '<p>';
        $html .= $GLOBALS['Language']->getText('plugin_git', 'gerrit_server_already_migrated', array($this->repository->getName(), $gerrit_project, $link));
        $html .= '</p>';
        $html .= '<div class="git_repomanagement_gerrit_more_description">';
        $html .= $GLOBALS['Language']->getText('plugin_git', 'gerrit_migrated_more_description', array($gerrit_project, $gerrit_server->getHost()));
        $html .= '</div>';

        $html .= '<form method="POST" action="'. $_SERVER['REQUEST_URI'] .'">';
        $html .= '<button type="submit" class="btn" name="'. $btn_name .'" value="1">';
        $html .= '<i class="icon-off"></i> '. $GLOBALS['Language']->getText('plugin_git', 'disconnect_gerrit_title');
        $html .= '</button>';
        $html .= '</form>';
        return $html;
    }

    private function getDisconnectFromGerritConfirmationScreen() {
        $html  = '';
        $html .= '<h3>'. $GLOBALS['Language']->getText('plugin_git', 'disconnect_gerrit_title') .'</h3>';

        $html .= '<form method="POST" action="/plugins/git/?group_id='. $this->repository->getProjectId() .'">';
        $html .= '<input type="hidden" name="action" value="disconnect_gerrit" />';
        $html .= '<input type="hidden" name="pane" value="'. $this->getIdentifier() .'" />';
        $html .= '<input type="hidden" id="repo_id" name="repo_id" value="'. $this->repository->getId() .'" />';

        $html .= '<div class="alert alert-block">';
        $html .= '<h4>'. $GLOBALS['Language']->getText('global', 'warning!') .'</h4>';
        $html .= '<p>'. $GLOBALS['Language']->getText('plugin_git', 'disconnect_gerrit_msg') .'</p>';
        $html .= '<p>';
        $html .= '<button type="submit" name="disconnect" value="1" class="btn btn-danger">'. $GLOBALS['Language']->getText('plugin_git', 'disconnect_gerrit_yes') .'</button> ';
        $html .= '<button type="button" class="btn" onclick="window.location=window.location;">'. $GLOBALS['Language']->getText('plugin_git', 'no') .'</button> ';
        $html .= '</p>';
        $html .= '</div>';

        $html .= '</form>';

        return $html;
    }
}
?>
