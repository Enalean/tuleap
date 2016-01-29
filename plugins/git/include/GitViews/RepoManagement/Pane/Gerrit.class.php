<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All Rights Reserved.
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

    const OPTION_DISCONNECT_GERRIT_PROJECT = 'gerrit_project_delete';
    const OPTION_DELETE_GERRIT_PROJECT     = 'delete';
    const OPTION_READONLY_GERRIT_PROJECT   = 'read';

    /**
     * @var Git_RemoteServer_GerritServer[]
     */
    private $gerrit_servers;

    /**
     *  @var Git_Driver_Gerrit_GerritDriverFactory
     */
    private $driver_factory;

    /**
     * @var Git_Drive_Gerrit_Template_Template[]
     */
    private $templates;

    public function __construct(
        GitRepository $repository,
        Codendi_Request $request,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        array $gerrit_servers,
        array $gerrit_config_templates
    ) {
        parent::__construct($repository, $request);
        $this->gerrit_servers = $gerrit_servers;
        $this->driver_factory = $driver_factory;
        $this->templates      = $gerrit_config_templates;
    }

    /**
     * @return bool true if the pane can be displayed
     */
    public function canBeDisplayed() {
        $platform_can_use_gerrit = false;

        EventManager::instance()->processEvent(
            GIT_EVENT_PLATFORM_CAN_USE_GERRIT,
            array(
                'platform_can_use_gerrit' => &$platform_can_use_gerrit
            )
        );

        return $platform_can_use_gerrit && count($this->gerrit_servers) > 0;
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
        if ($this->repository->isMigratedToGerrit()) {
            return $this->getContentAlreadyMigrated();
        }

        $html     = '';
        $disabled = '';

        if (! $this->repository->isCreated()) {
            $html .= '<div class="alert alert-info wait_creation">';
            $html .= $GLOBALS['Language']->getText('plugin_git', 'waiting_for_repo_creation');
            $html .= '</div>';

            $disabled = 'disabled=true';
        }

        $name_builder = new Git_RemoteServer_Gerrit_ProjectNameBuilder();

        $html .= '<h3>'. $GLOBALS['Language']->getText('plugin_git', 'gerrit_title') .'</h3>';
        $html .= '<form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id='. $this->repository->getProjectId() .'">';
        $html .= '<input type="hidden" id="action" name="action" value="migrate_to_gerrit" />';
        $html .= '<input type="hidden" name="pane" value="'. $this->getIdentifier() .'" />';
        $html .= '<input type="hidden" id="repo_id" name="repo_id" value="'. $this->repository->getId() .'" />';

        $html .= '<p>';
        $html .= $GLOBALS['Language']->getText('plugin_git', 'gerrit_migration_description', $this->repository->getName());
        $html .= '</p>';
        $html .= '<div class="git_repomanagement_gerrit_more_description">';
        $html .= $GLOBALS['Language']->getText('plugin_git', 'gerrit_migration_more_description', $name_builder->getGerritProjectName($this->repository));
        $html .= '</div>';
        $html .= '<p>';
        $html .= '<label for="gerrit_url">'. $GLOBALS['Language']->getText('plugin_git', 'gerrit_url') .'</label>';
        $html .= '<select name="remote_server_id" id="gerrit_url" '.$disabled.'>';
        $html .= '<option value="" selected="selected">'. $GLOBALS['Language']->getText('global', 'please_choose_dashed') .'</option>';
        $html .= $this->getServers();
        $html .= '</select>';
        $html .= '</p>';
        $html .= '<p>';
        $html .= '<label for="gerrit_template">'. $GLOBALS['Language']->getText('plugin_git', 'gerrit_template') .'</label>';
        $html .= '<select name="gerrit_template_id" id="gerrit_template" '.$disabled.'>';
        $html .= '<option value="" selected="selected">'. $GLOBALS['Language']->getText('global', 'please_choose_dashed') .'</option>';
        $html .= $this->getTemplates();
        $html .= '</select>';
        $html .= '</p>';

        $html .= '<p id="migrate_access_right"><input type="submit" name="save" value="'. $GLOBALS['Language']->getText('plugin_git', 'gerrit_migrate_to') .'" '.$disabled.' /></p>';
        $html .= '<div id="gerrit_past_project_delete" class="alert alert-info">
                    <p>'. $GLOBALS['Language']->getText('plugin_git', 'gerrit_past_project_warn') .'
                    </p>
                    <p>
                        <input type="submit" name="submit" value="'. $GLOBALS['Language']->getText('plugin_git', 'gerrit_past_project_delete') .'" />
                    </p>
                </div>';
        $html .= '<p id="gerrit_past_project_delete_plugin_diasabled" class="alert alert-info">
                    '. $GLOBALS['Language']->getText('plugin_git', 'gerrit_past_project_warn') .'
                </p>';
        $html .= '</form>';
        return $html;
    }

    private function getServers() {
        $html = '';
        foreach ($this->gerrit_servers as $server) {
            $driver         = $this->driver_factory->getDriver($server);
            $plugin_enabled = (int) $driver->isDeletePluginEnabled($server);
            $should_delete  = (int) $this->doesRemoteGerritProjectNeedDeleting($server);

            $html .= '<option
                        data-repo-delete="'.(int) $should_delete.'"
                        value="'.(int) $server->getId().'"
                        data-repo-delete-plugin-enabled="'.(int) $plugin_enabled.'">'
                    .$this->hp->purify($server->getBaseUrl()) .
                    '</option>';
        }

        return $html;
    }

    private function getTemplates() {
        $html = '<option
                        value="none">'
                .$GLOBALS['Language']->getText('plugin_git','none_gerrit_template') .
                '</option>
                 <option
                        value="default">'
                .$GLOBALS['Language']->getText('plugin_git','default_gerrit_template') .
                '</option>';

        foreach ($this->templates as $template) {
            $html .= '<option
                        value="'.(int) $template->getId().'">'
                    .$this->hp->purify($template->getName()) .
                    '</option>';
        }

        return $html;
    }

    private function doesRemoteGerritProjectNeedDeleting(Git_RemoteServer_GerritServer $server) {
        if ($server->getId() != $this->repository->getRemoteServerId()) {
            return false;
        }

        if (! $this->repository->wasPreviouslyMigratedButNotDeleted()) {
            return false;
        }

        $driver       = $this->getGerritDriverForRepository($this->repository);
        $project_name = $driver->getGerritProjectName($this->repository);
        try {
            if (! $driver->doesTheProjectExist($server, $project_name)) {
                return false;
            }
        } catch (Git_Driver_Gerrit_Exception $e) {
            return false;
        }

        return true;
    }

    private function getContentAlreadyMigrated() {
        $btn_name      = 'confirm_disconnect_gerrit';
        if ($this->request->get($btn_name)) {
            return $this->getDisconnectFromGerritConfirmationScreen();
        }

        $html  = '';
        $html .= '<fieldset class="gerrit_disconnect">';
        $html .= '<legend class="gerrit_disconnect">'.$GLOBALS['Language']->getText('plugin_git', 'gerrit_title').'</legend>';
        $html .= $this->getMessageAccordingToMigrationStatus();
        $html .= '</fieldset>';

        $html .= '<form method="POST" action="'. $_SERVER['REQUEST_URI'] .'">';
        $html .= '<fieldset class="gerrit_disconnect">';
        $html .= '<legend class="gerrit_disconnect">' . $GLOBALS['Language']->getText('plugin_git', 'disconnect_gerrit_title') . '</legend>';
        $html .= $this->getDisconnectFromGerritOptions();
        $html .= '<button type="submit" class="btn" name="'. $btn_name .'" value="1">';
        $html .= '<i class="icon-off"></i> ' . $GLOBALS['Language']->getText('plugin_git', 'disconnect_gerrit_button');
        $html .= '</button>';
        $html .= '</fieldset>';
        $html .= '</form>';
        return $html;
    }

    private function getMessageAccordingToMigrationStatus() {
        $project_creator_status = new Git_Driver_Gerrit_ProjectCreatorStatus(
            new Git_Driver_Gerrit_ProjectCreatorStatusDao()
        );
        switch ($project_creator_status->getStatus($this->repository)) {
            case Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE:
                return '';

            case null:
            case Git_Driver_Gerrit_ProjectCreatorStatus::DONE:
                return $this->getMigratedToGerritInfo();

            case Git_Driver_Gerrit_ProjectCreatorStatus::ERROR:
                return $this->getMigratedToGerritError($project_creator_status);
        }
    }

    private function getMigratedToGerritInfo() {
        $driver         = $this->getGerritDriverForRepository($this->repository);
        $gerrit_project = $driver->getGerritProjectName($this->repository);
        $gerrit_server  = $this->getGerritServerForRepository($this->repository);
        $link           = $gerrit_server->getProjectAdminUrl($gerrit_project);

        $html  = '';
        $html .= '<p>';
        $html .= $GLOBALS['Language']->getText('plugin_git', 'gerrit_server_already_migrated', array($this->repository->getName(), $gerrit_project, $link));
        $html .= '</p>';
        $html .= '<div class="git_repomanagement_gerrit_more_description">';
        $html .= $GLOBALS['Language']->getText('plugin_git', 'gerrit_migrated_more_description', array($gerrit_project, $gerrit_server->getHost()));
        $html .= '</div>';
        return $html;
    }

    private function getMigratedToGerritError(Git_Driver_Gerrit_ProjectCreatorStatus $status) {
        $date = DateHelper::timeAgoInWords($status->getEventDate($this->repository), false, true);
        return '<div class="alert alert-error">'.$GLOBALS['Language']->getText('plugin_git', 'gerrit_server_migration_error', array($date)).'</div>'.
               '<pre class="pre-scrollable">'.$status->getLog($this->repository).'</pre>';
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
        $html .= '<input type="hidden" name="' . self::OPTION_DISCONNECT_GERRIT_PROJECT . '" value="' . $this->hp->purify($this->request->get(self::OPTION_DISCONNECT_GERRIT_PROJECT)) . '"/>';
        $html .= '<button type="submit" name="disconnect" value="1" class="btn btn-danger">'. $GLOBALS['Language']->getText('plugin_git', 'disconnect_gerrit_yes') .'</button> ';
        $html .= '<button type="button" class="btn" onclick="window.location=window.location;">'. $GLOBALS['Language']->getText('plugin_git', 'no') .'</button> ';
        $html .= '</p>';
        $html .= '</div>';

        $html .= '</form>';

        return $html;
    }

    private function getDisconnectFromGerritOptions() {
        $gerrit_server = $this->getGerritServerForRepository($this->repository);
        $html = '';

        $driver = $this->driver_factory->getDriver($gerrit_server);
        if ($driver->isDeletePluginEnabled($gerrit_server)) {
            $html .= '<label class="radio"><input type="radio" name="' . self::OPTION_DISCONNECT_GERRIT_PROJECT . '" value="'.self::OPTION_DELETE_GERRIT_PROJECT.'"/>'
                . $GLOBALS['Language']->getText('plugin_git', 'gerrit_project_delete')
                . '</label>';
        }

        $html .='<label class="radio"><input type="radio" name="' . self::OPTION_DISCONNECT_GERRIT_PROJECT . '" value="'.self::OPTION_READONLY_GERRIT_PROJECT.'"/>'
            . $GLOBALS['Language']->getText('plugin_git', 'gerrit_project_readonly')
            . '</label>'
            . '<label class="radio"><input type="radio" name="' . self::OPTION_DISCONNECT_GERRIT_PROJECT . '"/>'
            . $GLOBALS['Language']->getText('plugin_git', 'gerrit_project_leave')
            . '</label>';

        return $html;
    }

    private function getGerritDriverForRepository(GitRepository $repository) {
        $server = $this->getGerritServerForRepository($repository);

        return $this->driver_factory->getDriver($server);
    }

    private function getGerritServerForRepository(GitRepository $repository) {
        return $this->gerrit_servers[$repository->getRemoteServerId()];
    }
}
?>
