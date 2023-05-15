<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Git\GitViews\RepoManagement\Pane;

use Codendi_HTMLPurifier;
use Codendi_Request;
use DateHelper;
use Git_Driver_Gerrit_Exception;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Driver_Gerrit_ProjectCreatorStatusDao;
use Git_RemoteServer_Gerrit_ProjectNameBuilder;
use Git_RemoteServer_GerritServer;
use GitRepository;
use Tuleap\date\RelativeDatesAssetsRetriever;
use Tuleap\Git\Driver\Gerrit\UnsupportedGerritVersionException;
use Tuleap\Git\RemoteServer\GerritCanMigrateChecker;

class Gerrit extends Pane
{
    public const OPTION_DISCONNECT_GERRIT_PROJECT = 'gerrit_project_delete';
    public const OPTION_DELETE_GERRIT_PROJECT     = 'delete';
    public const OPTION_READONLY_GERRIT_PROJECT   = 'read-only';

    /**
     * @var Git_RemoteServer_GerritServer[]
     */
    private $gerrit_servers;

    /**
     *  @var Git_Driver_Gerrit_GerritDriverFactory
     */
    private $driver_factory;

    /**
     * @var GerritCanMigrateChecker
     */
    private $gerrit_can_migrate_checker;

    /**
     * @var \Git_Driver_Gerrit_Template_Template[]
     */
    private $templates;
    /**
     * @var \ProjectManager
     */
    private $project_manager;

    public function __construct(
        GitRepository $repository,
        Codendi_Request $request,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        GerritCanMigrateChecker $gerrit_can_migrate_checker,
        array $gerrit_servers,
        array $gerrit_config_templates,
        \ProjectManager $project_manager,
    ) {
        parent::__construct($repository, $request);
        $this->gerrit_servers             = $gerrit_servers;
        $this->driver_factory             = $driver_factory;
        $this->gerrit_can_migrate_checker = $gerrit_can_migrate_checker;
        $this->templates                  = $gerrit_config_templates;
        $this->repository                 = $repository;
        $this->project_manager            = $project_manager;
    }

    /**
     * @return bool true if the pane can be displayed
     */
    public function canBeDisplayed()
    {
        return $this->gerrit_can_migrate_checker->canMigrate($this->repository->getProject());
    }

    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    public function getIdentifier()
    {
        return 'gerrit';
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    public function getTitle()
    {
        return dgettext('tuleap-git', 'Gerrit');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    public function getContent()
    {
        if ($this->repository->isMigratedToGerrit()) {
            try {
                $user = \HTTPRequest::instance()->getCurrentUser();
                return $this->getContentAlreadyMigrated($user);
            } catch (UnsupportedGerritVersionException $exception) {
                return '<p class="alert alert-error">' .
                    dgettext('tuleap-git', 'You are using a version of Gerrit that is not supported, please contact your site administrators.') .
                    '</p>';
            }
        }

        $html     = '';
        $disabled = '';

        if (! $this->repository->isCreated()) {
            $html .= '<div class="alert alert-info wait_creation">';
            $html .= dgettext('tuleap-git', 'The repository is in queue for creation. Please check back here in a few minutes');
            $html .= '</div>';

            $disabled = 'disabled=true';
        }

        $parent                      = $this->project_manager->getParentProject($this->repository->getProjectId());
        $parent_is_suspended         = false;
        $parent_is_suspended_message = "";
        if ($parent !== null && ! $parent->isActive()) {
            $disabled                    = 'disabled=true';
            $parent_is_suspended_message = dgettext('tuleap-git', 'Parent project is not active, you are not allowed to migrate your repository on gerrit.');
            $parent_is_suspended         = true;
        }

        $name_builder = new Git_RemoteServer_Gerrit_ProjectNameBuilder();

        $html .= '<h2>' . dgettext('tuleap-git', 'Migration to Gerrit') . '</h2>';
        $html .= '<form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id=' . $this->repository->getProjectId() . '">';
        $html .= '<input type="hidden" id="action" name="action" value="migrate_to_gerrit" />';
        $html .= $this->csrf_token()->fetchHTMLInput();
        $html .= '<input type="hidden" name="pane" value="' . $this->getIdentifier() . '" />';
        $html .= '<input type="hidden" id="repo_id" name="repo_id" value="' . $this->repository->getId() . '" />';

        if ($parent_is_suspended === true) {
            $html .= '<p class="alert alert-danger">
                    ' . $parent_is_suspended_message . '
                </p>';
        }

        $html .= '<p>';
        $html .= sprintf(dgettext('tuleap-git', 'You are about to migrate your repository <em>%1$s</em> to Gerrit.'), $this->repository->getName());
        $html .= '</p>';
        $html .= '<div class="git_repomanagement_gerrit_more_description">';
        $html .= sprintf(dgettext('tuleap-git', 'The management of the repository (permissions, ...) will be delegated to Gerrit: <ul><li>A project <em>%1$s</em> will be created on the chosen server.</li><li>The current repository will become a mirror of the Gerrit reference.</li></ul>'), $name_builder->getGerritProjectName($this->repository));
        $html .= '</div>';
        $html .= '<p>';
        $html .= '<label for="gerrit_url">' . dgettext('tuleap-git', 'Please choose the Gerrit server:') . '</label>';
        $html .= '<select name="remote_server_id" id="gerrit_url" ' . $disabled . '>';
        $html .= '<option value="" selected="selected">' . $GLOBALS['Language']->getText('global', 'please_choose_dashed') . '</option>';
        $html .= $this->getServers();
        $html .= '</select>';
        $html .= '</p>';
        $html .= '<p>';
        $html .= '<label for="gerrit_template">' . dgettext('tuleap-git', 'Please choose a permission\'s configuration template for the migration,<br />they will be applied to the migrated repository on Gerrit:') . '</label>';
        $html .= '<select name="gerrit_template_id" id="gerrit_template" ' . $disabled . '>';
        $html .= '<option value="" selected="selected">' . $GLOBALS['Language']->getText('global', 'please_choose_dashed') . '</option>';
        $html .= $this->getTemplates();
        $html .= '</select>';
        $html .= '</p>';

        $html .= '<p id="migrate_access_right"><input type="submit" name="save" value="' . dgettext('tuleap-git', 'Migrate repo to Gerrit') . '" ' . $disabled . ' /></p>';
        $html .= '<div id="gerrit_past_project_delete" class="alert alert-info">
                    <p>' . dgettext('tuleap-git', 'This repository was migrated to the server in the past. You cannot migrate again without deleting the project on the Gerrit server') . '
                    </p>
                    <p>
                        <input type="submit" name="submit" value="' . dgettext('tuleap-git', 'Delete gerrit project') . '" />
                    </p>
                </div>';
        if ($parent_is_suspended !== true) {
            $html .= '<p id="gerrit_past_project_delete_plugin_diasabled" class="alert alert-info">
                    ' . dgettext('tuleap-git', 'This repository was migrated to the server in the past. You cannot migrate again without deleting the project on the Gerrit server') . '
                </p>';
        }
        $html .= '</form>';
        return $html;
    }

    private function getServers()
    {
        $html = '';
        foreach ($this->gerrit_servers as $server) {
            $driver = $this->driver_factory->getDriver($server);
            try {
                $plugin_enabled = (int) $driver->isDeletePluginEnabled($server);
                $should_delete  = (int) $this->doesRemoteGerritProjectNeedDeleting($server);
            } catch (UnsupportedGerritVersionException $exception) {
                continue;
            }

            $html .= '<option
                        data-repo-delete="' . (int) $should_delete . '"
                        value="' . (int) $server->getId() . '"
                        data-repo-delete-plugin-enabled="' . (int) $plugin_enabled . '">'
                    . $this->hp->purify($server->getBaseUrl()) .
                    '</option>';
        }

        return $html;
    }

    private function getTemplates()
    {
        $html = '<option
                        value="none">'
                . dgettext('tuleap-git', 'No pre-set permissions') .
                '</option>
                 <option
                        value="default">'
                . dgettext('tuleap-git', 'Permissions defined in Git') .
                '</option>';

        foreach ($this->templates as $template) {
            $html .= '<option
                        value="' . (int) $template->getId() . '">'
                    . $this->hp->purify($template->getName()) .
                    '</option>';
        }

        return $html;
    }

    private function doesRemoteGerritProjectNeedDeleting(Git_RemoteServer_GerritServer $server)
    {
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

    private function getContentAlreadyMigrated(\PFUser $user): string
    {
        $btn_name = 'confirm_disconnect_gerrit';
        if ($this->request->get($btn_name)) {
            return $this->getDisconnectFromGerritConfirmationScreen();
        }

        $html  = '';
        $html .= '<fieldset class="gerrit_disconnect">';
        $html .= '<legend class="gerrit_disconnect">' . dgettext('tuleap-git', 'Migration to Gerrit') . '</legend>';
        $html .= $this->getMessageAccordingToMigrationStatus($user);
        $html .= '</fieldset>';

        $html .= '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '">';
        $html .= $this->csrf_token()->fetchHTMLInput();
        $html .= '<fieldset class="gerrit_disconnect">';
        $html .= '<legend class="gerrit_disconnect">' . dgettext('tuleap-git', 'Disconnect from Gerrit') . '</legend>';
        $html .= $this->getDisconnectFromGerritOptions();
        $html .= '<button type="submit" class="btn" name="' . $btn_name . '" value="1">';
        $html .= '<i class="fa fa-power-off"></i> ' . dgettext('tuleap-git', 'Disconnect');
        $html .= '</button>';
        $html .= '</fieldset>';
        $html .= '</form>';
        return $html;
    }

    private function getMessageAccordingToMigrationStatus(\PFUser $user): string
    {
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
                return $this->getMigratedToGerritError($project_creator_status, $user);
        }
         return '';
    }

    private function getMigratedToGerritInfo(): string
    {
        $purifier       = Codendi_HTMLPurifier::instance();
        $driver         = $this->getGerritDriverForRepository($this->repository);
        $gerrit_project = $driver->getGerritProjectName($this->repository);
        $gerrit_server  = $this->getGerritServerForRepository($this->repository);
        $link           = $gerrit_server->getProjectAdminUrl($gerrit_project);

        $html  = '';
        $html .= '<p>';
        $html .= sprintf(dgettext('tuleap-git', 'The repository <em>%1$s</em> has already been migrated to the Gerrit project: <a href="%3$s">%2$s</a>.'), $purifier->purify($this->repository->getName()), $purifier->purify($gerrit_project), $purifier->purify($link));
        $html .= '</p>';
        $html .= '<div class="git_repomanagement_gerrit_more_description">';
        $html .= sprintf(dgettext('tuleap-git', 'As a reminder, the management of the repository (permissions, ...) has been delegated to Gerrit: <ul><li>A project <em>%1$s</em> has been created on the server <a href="%2$s">%2$s</a>.</li><li>The current repository is now a mirror of the Gerrit reference.</li></ul>'), $purifier->purify($gerrit_project), $purifier->purify($gerrit_server->getBaseUrl()));
        $html .= '</div>';
        return $html;
    }

    private function getMigratedToGerritError(Git_Driver_Gerrit_ProjectCreatorStatus $status, \PFUser $user): string
    {
        $GLOBALS['Response']->addJavascriptAsset(RelativeDatesAssetsRetriever::getAsJavascriptAssets());
        $date = DateHelper::relativeDateInlineContext((int) $status->getEventDate($this->repository), $user);
        return '<div class="alert alert-error">' . sprintf(dgettext('tuleap-git', 'The migration failed %1$s, the gerrit repository is probably <strong>corrupted or partially migrated</strong>, do not use it and contact your site administration'), $date) . '</div>' .
               '<pre class="pre-scrollable">' . $status->getLog($this->repository) . '</pre>';
    }

    private function getDisconnectFromGerritConfirmationScreen()
    {
        $html  = '';
        $html .= '<h3>' . dgettext('tuleap-git', 'Disconnect from Gerrit') . '</h3>';

        $html .= '<form method="POST" action="/plugins/git/?group_id=' . $this->repository->getProjectId() . '">';
        $html .= $this->csrf_token()->fetchHTMLInput();
        $html .= '<input type="hidden" name="action" value="disconnect_gerrit" />';
        $html .= '<input type="hidden" name="pane" value="' . $this->getIdentifier() . '" />';
        $html .= '<input type="hidden" id="repo_id" name="repo_id" value="' . $this->repository->getId() . '" />';

        $html .= '<div class="alert alert-block">';
        $html .= '<h4>' . $GLOBALS['Language']->getText('global', 'warning') . '</h4>';
        $html .= '<p>' . dgettext('tuleap-git', 'This operation <strong>cannot</strong> be undone. This repository will never again be managed by a gerrit server. Do you confirm the disconnection?') . '</p>';
        $html .= '<p>';
        $html .= '<input type="hidden" name="' . self::OPTION_DISCONNECT_GERRIT_PROJECT . '" value="' . $this->hp->purify($this->request->get(self::OPTION_DISCONNECT_GERRIT_PROJECT)) . '"/>';
        $html .= '<button type="submit" name="disconnect" value="1" class="btn btn-danger">' . dgettext('tuleap-git', 'Yes, I want to disconnect from Gerrit') . '</button> ';
        $html .= '<button type="button" class="btn" onclick="window.location=window.location;">' . dgettext('tuleap-git', 'No') . '</button> ';
        $html .= '</p>';
        $html .= '</div>';

        $html .= '</form>';

        return $html;
    }

    private function getDisconnectFromGerritOptions()
    {
        $gerrit_server = $this->getGerritServerForRepository($this->repository);
        $html          = '';

        $driver = $this->driver_factory->getDriver($gerrit_server);
        if ($driver->isDeletePluginEnabled($gerrit_server)) {
            $html .= '<label class="radio"><input type="radio" name="' . self::OPTION_DISCONNECT_GERRIT_PROJECT . '" value="' . self::OPTION_DELETE_GERRIT_PROJECT . '"/>'
                . dgettext('tuleap-git', 'Also Delete project on Gerrit')
                . '</label>';
        }

        $html .= '<label class="radio"><input type="radio" name="' . self::OPTION_DISCONNECT_GERRIT_PROJECT . '" value="' . self::OPTION_READONLY_GERRIT_PROJECT . '"/>'
            . dgettext('tuleap-git', 'Make project on Gerrit Read-Only')
            . '</label>'
            . '<label class="radio"><input type="radio" name="' . self::OPTION_DISCONNECT_GERRIT_PROJECT . '"/>'
            . dgettext('tuleap-git', 'Leave project on Gerrit')
            . '</label>';

        return $html;
    }

    private function getGerritDriverForRepository(GitRepository $repository)
    {
        $server = $this->getGerritServerForRepository($repository);

        return $this->driver_factory->getDriver($server);
    }

    private function getGerritServerForRepository(GitRepository $repository)
    {
        return $this->gerrit_servers[$repository->getRemoteServerId()];
    }
}
