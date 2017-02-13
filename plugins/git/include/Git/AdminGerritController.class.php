<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All Rights Reserved.
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Git\AdminAllowedProjectsGerritPresenter;
use Tuleap\Git\GerritServerResourceRestrictor;

class Git_AdminGerritController {

    private $servers;

    /** @var Git_RemoteServer_GerritServerFactory */
    private $gerrit_server_factory;

    /** @var CSRFSynchronizerToken */
    private $csrf;

    /** @var AdminPageRenderer */
    private $admin_page_renderer;

    /**
     * @var GerritServerResourceRestrictor
     */
    private $gerrit_ressource_restrictor;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        CSRFSynchronizerToken                $csrf,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        AdminPageRenderer                    $admin_page_renderer,
        GerritServerResourceRestrictor       $gerrit_ressource_restrictor,
        ProjectManager                       $project_manager
    ) {
        $this->gerrit_server_factory       = $gerrit_server_factory;
        $this->csrf                        = $csrf;
        $this->admin_page_renderer         = $admin_page_renderer;
        $this->gerrit_ressource_restrictor = $gerrit_ressource_restrictor;
        $this->project_manager             = $project_manager;
    }

    public function process(Codendi_Request $request) {
        if ($request->get('action') == 'edit-gerrit-server') {
            $this->updateGerritServer($request);
        } else if ($request->get('action') == 'add-gerrit-server') {
            $this->addGerritServer($request);
        } else if ($request->get('action') == 'delete-gerrit-server') {
            $this->deleteGerritServer($request);
        } elseif ($request->get('action') == 'set-gerrit-server-restriction') {
            $this->setGerritServerRestriction($request);
        } elseif ($request->get('action') == 'update-allowed-project-list') {
            $this->updateAllowedProjectList($request);
        }
    }

    /**
     * @return Git_RemoteServer_GerritServer
     */
    private function getGerritServerFromRequest(Codendi_Request $request)
    {
        $gerrit_server_id = $request->get('gerrit_server_id');

        try {
            $gerrit_server = $this->gerrit_server_factory->getServerById($gerrit_server_id);
        }  catch (Git_RemoteServer_NotFoundException $exception) {
            $title = $GLOBALS['Language']->getText('plugin_git', 'descriptor_name');

            $this->redirectToGerritServerList($title);
        }

        return $gerrit_server;
    }

    private function updateAllowedProjectList(Codendi_Request $request)
    {
        $gerrit_server         = $this->getGerritServerFromRequest($request);
        $project_to_add        = $request->get('project-to-allow');
        $project_ids_to_remove = $request->get('project-ids-to-revoke');

        if ($request->get('allow-project') && ! empty($project_to_add)) {
            $this->allowProjectForGerritServer($gerrit_server, $project_to_add);

        } elseif ($request->get('revoke-project') && ! empty($project_ids_to_remove)) {
            $this->revokeProjectsForGerritServer($gerrit_server, $project_ids_to_remove);
        }

        $GLOBALS['Response']->redirect(
            '/plugins/git/admin/?view=gerrit_servers_restriction&action=manage-allowed-projects&gerrit_server_id=' .
            urlencode($gerrit_server->getId())
        );
    }

    private function allowProjectForGerritServer(Git_RemoteServer_GerritServer $gerrit_server, $project_to_add) {
        $project = $this->project_manager->getProjectFromAutocompleter($project_to_add);

        if ($project && $this->gerrit_ressource_restrictor->allowProject($gerrit_server, $project)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('plugin_git', 'gerrit_servers_allowed_project_allow_project')
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_git', 'gerrit_servers_allowed_project_update_project_list_error')
            );
        }
    }

    private function revokeProjectsForGerritServer(Git_RemoteServer_GerritServer $gerrit_server, $project_ids) {
        $GLOBALS['Response']->addFeedback(
            Feedback::WARN,
            $GLOBALS['Language']->getText('plugin_git', 'gerrit_servers_under_implementation')
        );
    }

    private function setGerritServerRestriction(Codendi_Request $request)
    {
        $gerrit_server = $this->getGerritServerFromRequest($request);

        $this->checkSynchronizerToken(
            '/plugins/git/admin/?view=gerrit_servers_restriction&action=set-gerrit-server-restriction&gerrit_server_id=' .
            urlencode($gerrit_server->getId())
        );

        $this->restrictGerritServer($request, $gerrit_server);

        $GLOBALS['Response']->redirect(
            '/plugins/git/admin/?view=gerrit_servers_restriction&action=manage-allowed-projects&gerrit_server_id=' .
            urlencode($gerrit_server->getId())
        );
    }

    private function restrictGerritServer(Codendi_Request $request, Git_RemoteServer_GerritServer $gerrit_server)
    {
        $all_allowed = $request->get('all-allowed');

        if ($all_allowed) {
            $this->unsetRestriction($gerrit_server);
        } else {
            $this->setRestricted($gerrit_server);
        }
    }

    private function setRestricted(Git_RemoteServer_GerritServer $gerrit_server)
    {
        if ($this->gerrit_ressource_restrictor->setRestricted($gerrit_server)) {
            $GLOBALS['Response']->addFeedback(
                'info',
                $GLOBALS['Language']->getText('plugin_git', 'gerrit_servers_allowed_project_set_restricted')
            );
        }
    }

    private function unsetRestriction(Git_RemoteServer_GerritServer $gerrit_server)
    {
        if ($this->gerrit_ressource_restrictor->unsetRestriction($gerrit_server)) {
            $GLOBALS['Response']->addFeedback(
                'info',
                $GLOBALS['Language']->getText('plugin_git', 'gerrit_servers_allowed_project_unset_restricted')
            );
        }
    }

    private function redirectToGerritServerList($title)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('plugin_git', 'gerrit_servers_id_does_not_exist')
        );

        $this->renderGerritServerList($title);
    }

    private function checkSynchronizerToken($url)
    {
        $token = new CSRFSynchronizerToken($url);
        $token->check();
    }

    private function addGerritServer(Codendi_Request $request)
    {
        $request_gerrit_server = $request->params;
        $this->csrf->check();
        $this->addServer($request_gerrit_server);
        $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=gerrit_servers_admin');
    }

    private function deleteGerritServer($request)
    {
        $request_gerrit_server = $request->params;
        $this->csrf->check();
        $this->deleteServer($request_gerrit_server);
        $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=gerrit_servers_admin');
    }

    private function updateGerritServer(Codendi_Request $request) {
        $request_gerrit_server = $request->params;
        $this->csrf->check();
        $this->updateServer($request_gerrit_server);
        $GLOBALS['Response']->redirect('/plugins/git/admin/?pane=gerrit_servers_admin');
    }

    public function display(Codendi_Request $request) {
        $title = $GLOBALS['Language']->getText('plugin_git', 'descriptor_name');

        switch ($request->get('action')) {
            case 'manage-allowed-projects':
                try {
                    $presenter     = $this->getManageAllowedProjectsPresenter($request);
                    $template_path = ForgeConfig::get('codendi_dir') . '/src/templates/resource_restrictor';

                    $this->admin_page_renderer->renderAPresenter(
                        $title,
                        $template_path,
                        $presenter->getTemplate(),
                        $presenter
                    );
                } catch (Git_RemoteServer_NotFoundException $exception) {
                    $this->redirectToGerritServerList($title);
                }

                break;
            default:
                $this->renderGerritServerList($title);
                break;
        }
    }

    private function renderGerritServerList($title)
    {
        $admin_presenter = new Git_AdminGerritPresenter(
            $title,
            $this->csrf,
            $this->getListOfGerritServersPresenters()
        );

        $this->admin_page_renderer->renderANoFramedPresenter(
            $title,
            dirname(GIT_BASE_DIR) . '/templates',
            'admin-plugin',
            $admin_presenter
        );
    }

    private function getManageAllowedProjectsPresenter(Codendi_Request $request) {
        $gerrit_server_id = $request->get('gerrit_server_id');
        $gerrit_server    = $this->gerrit_server_factory->getServerById($gerrit_server_id);

        return new AdminAllowedProjectsGerritPresenter(
            $gerrit_server,
            $this->gerrit_ressource_restrictor->searchAllowedProjects($gerrit_server),
            $this->gerrit_ressource_restrictor->isRestricted($gerrit_server)
        );
    }

    private function fetchGerritServers() {
        if (empty($this->servers)) {
            $this->servers = $this->gerrit_server_factory->getServers();
        }
    }

    private function getListOfGerritServersPresenters() {
        $this->fetchGerritServers();

        $list_of_presenters = array();
        foreach ($this->servers as $server) {
            $is_used = $this->gerrit_server_factory->isServerUsed($server);
            $list_of_presenters[] = new Git_RemoteServer_GerritServerPresenter($server, $is_used);
        }

        return $list_of_presenters;
    }

    private function addServer($request_gerrit_server)
    {
        if ($this->allGerritServerParamsRequiredExist($request_gerrit_server)) {
            $host                 = $request_gerrit_server['host'];
            $ssh_port             = $request_gerrit_server['ssh_port'];
            $http_port            = $request_gerrit_server['http_port'];
            $login                = $request_gerrit_server['login'];
            $identity_file        = $request_gerrit_server['identity_file'];
            $replication_ssh_key  = $request_gerrit_server['replication_key'];
            $use_ssl              = isset($request_gerrit_server['use_ssl'])  ? $request_gerrit_server['use_ssl'] : false;
            $gerrit_version       = $request_gerrit_server['gerrit_version'];
            $http_password        = $request_gerrit_server['http_password'];
            $replication_password = $request_gerrit_server['replication_password'];
            $auth_type            = $request_gerrit_server['auth_type'];

            $server = new Git_RemoteServer_GerritServer(
                0,
                $host,
                $ssh_port,
                $http_port,
                $login,
                $identity_file,
                $replication_ssh_key,
                $use_ssl,
                $gerrit_version,
                $http_password,
                '',
                $auth_type
            );

            $this->gerrit_server_factory->save($server);
            $this->servers[$server->getId()] = $server;

            $this->updateReplicationPassword($server, $replication_password);
        }
    }

    private function deleteServer($request_gerrit_server)
    {
        $server_id = $request_gerrit_server['gerrit_server_id'];
        if (isset($server_id)) {
            $server = $this->gerrit_server_factory->getServerById($server_id);
            $this->gerrit_server_factory->delete($server);
        }
    }

    private function updateServer($request_gerrit_server)
    {
        $server_id = $request_gerrit_server['gerrit_server_id'];
        if (isset($server_id)) {
            $server = $this->gerrit_server_factory->getServerById($server_id);

            if ($this->allGerritServerParamsRequiredExist($request_gerrit_server)) {
                $host                 = $request_gerrit_server['host'];
                $ssh_port             = $request_gerrit_server['ssh_port'];
                $http_port            = $request_gerrit_server['http_port'];
                $login                = $request_gerrit_server['login'];
                $identity_file        = $request_gerrit_server['identity_file'];
                $replication_ssh_key  = $request_gerrit_server['replication_key'];
                $use_ssl              = isset($request_gerrit_server['use_ssl'])  ? $request_gerrit_server['use_ssl'] : false;
                $gerrit_version       = $request_gerrit_server['gerrit_version'];
                $http_password        = $request_gerrit_server['http_password'];
                $replication_password = $request_gerrit_server['replication_password'];
                $auth_type            = $request_gerrit_server['auth_type'];

                if ($host != $server->getHost() ||
                    $ssh_port != $server->getSSHPort() ||
                    $http_port != $server->getHTTPPort() ||
                    $login != $server->getLogin() ||
                    $identity_file != $server->getIdentityFile() ||
                    $replication_ssh_key != $server->getReplicationKey() ||
                    $use_ssl != $server->usesSSL() ||
                    $gerrit_version != $server->getGerritVersion() ||
                    $http_password != $server->getHTTPPassword() ||
                    $auth_type != $server->getAuthType()
                ) {
                    $server
                        ->setHost($host)
                        ->setSSHPort($ssh_port)
                        ->setHTTPPort($http_port)
                        ->setLogin($login)
                        ->setIdentityFile($identity_file)
                        ->setReplicationKey($replication_ssh_key)
                        ->setUseSSL($use_ssl)
                        ->setGerritVersion($gerrit_version)
                        ->setHTTPPassword($http_password)
                        ->setAuthType($auth_type);

                    $this->gerrit_server_factory->save($server);
                    $this->servers[$server->getId()] = $server;
                }

                $this->updateReplicationPassword($server, $replication_password);
            }
        }
    }

    private function updateReplicationPassword(Git_RemoteServer_GerritServer $server, $replication_password)
    {
        if (! hash_equals($server->getReplicationPassword(), $replication_password)) {
            $server->setReplicationPassword($replication_password);
            $this->gerrit_server_factory->updateReplicationPassword($server);

            $this->servers[$server->getId()] = $server;
        }
    }

    private function allGerritServerParamsRequiredExist($request_gerrit_server)
    {
        return (isset($request_gerrit_server['host']) && ! empty($request_gerrit_server['host'])) &&
        (isset($request_gerrit_server['ssh_port']) && ! empty($request_gerrit_server['ssh_port'])) &&
        (isset($request_gerrit_server['http_port']) && ! empty($request_gerrit_server['http_port'])) &&
        (isset($request_gerrit_server['login']) && ! empty($request_gerrit_server['login'])) &&
        (isset($request_gerrit_server['identity_file']) && ! empty($request_gerrit_server['identity_file'])) &&
        (isset($request_gerrit_server['replication_key']) && ! empty($request_gerrit_server['replication_key'])) &&
        (isset($request_gerrit_server['gerrit_version']) && ! empty($request_gerrit_server['gerrit_version'])) &&
        (isset($request_gerrit_server['http_password']) && ! empty($request_gerrit_server['http_password'])) &&
        (isset($request_gerrit_server['replication_password']) && ! empty($request_gerrit_server['replication_password'])) &&
        (isset($request_gerrit_server['auth_type']) && ! empty($request_gerrit_server['auth_type']));
    }
}
