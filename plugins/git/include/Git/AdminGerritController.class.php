<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
use Tuleap\Git\AdminGerritBuilder;
use Tuleap\Git\GerritServerResourceRestrictor;
use Tuleap\Git\RemoteServer\Gerrit\Restrictor;
use Tuleap\Layout\IncludeAssets;

class Git_AdminGerritController //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    private $servers;

    /** @var Git_RemoteServer_GerritServerFactory */
    private $gerrit_server_factory;

    /** @var CSRFSynchronizerToken */
    private $csrf;

    /** @var AdminPageRenderer */
    private $admin_page_renderer;
    /**
     * @var AdminGerritBuilder
     */
    private $admin_gerrit_builder;

    /**
     * @var GerritServerResourceRestrictor
     */
    private $gerrit_ressource_restrictor;

    /**
     * @var Restrictor
     */
    private $gerrit_restrictor;
    /**
     * @var IncludeAssets
     */
    private $include_assets;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        AdminPageRenderer $admin_page_renderer,
        GerritServerResourceRestrictor $gerrit_ressource_restrictor,
        Restrictor $gerrit_restrictor,
        AdminGerritBuilder $admin_gerrit_builder,
        IncludeAssets $include_assets
    ) {
        $this->gerrit_server_factory       = $gerrit_server_factory;
        $this->csrf                        = $csrf;
        $this->admin_page_renderer         = $admin_page_renderer;
        $this->gerrit_ressource_restrictor = $gerrit_ressource_restrictor;
        $this->gerrit_restrictor           = $gerrit_restrictor;
        $this->admin_gerrit_builder        = $admin_gerrit_builder;
        $this->include_assets              = $include_assets;
    }

    public function process(Codendi_Request $request)
    {
        if ($request->get('action') == 'edit-gerrit-server') {
            $this->updateGerritServer($request);
        } elseif ($request->get('action') == 'add-gerrit-server') {
            $this->addGerritServer($request);
        } elseif ($request->get('action') == 'delete-gerrit-server') {
            $this->deleteGerritServer($request);
        } elseif ($request->get('action') == 'set-gerrit-server-restriction') {
            $this->gerrit_restrictor->setGerritServerRestriction($request);
        } elseif ($request->get('action') == 'update-allowed-project-list') {
            $this->gerrit_restrictor->updateAllowedProjectList($request);
        }
    }

    private function addGerritServer(Codendi_Request $request)
    {
        $request_gerrit_server = $request->params;
        $this->csrf->check();
        $this->addServer($request_gerrit_server);
        $GLOBALS['Response']->redirect(GIT_SITE_ADMIN_BASE_URL . '?pane=gerrit_servers_admin');
    }

    private function deleteGerritServer($request)
    {
        $request_gerrit_server = $request->params;
        $this->csrf->check();
        $this->deleteServer($request_gerrit_server);
        $GLOBALS['Response']->redirect(GIT_SITE_ADMIN_BASE_URL . '?pane=gerrit_servers_admin');
    }

    private function updateGerritServer(Codendi_Request $request)
    {
        $request_gerrit_server = $request->params;
        $this->csrf->check();
        $this->updateServer($request_gerrit_server);
        $GLOBALS['Response']->redirect(GIT_SITE_ADMIN_BASE_URL . '?pane=gerrit_servers_admin');
    }

    public function display(Codendi_Request $request)
    {
        $title = dgettext('tuleap-git', 'Git');

        switch ($request->get('action')) {
            case 'manage-allowed-projects':
                try {
                    $presenter     = $this->getManageAllowedProjectsPresenter($request);
                    $template_path = ForgeConfig::get('codendi_dir') . '/src/templates/resource_restrictor';

                    $GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/manage-allowed-projects-on-resource.js');

                    $this->admin_page_renderer->renderAPresenter(
                        $title,
                        $template_path,
                        $presenter->getTemplate(),
                        $presenter
                    );
                } catch (Git_RemoteServer_NotFoundException $exception) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::ERROR,
                        dgettext('tuleap-git', 'The requested Gerrit server does not exist.')
                    );

                    $this->renderGerritServerList($title);
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

        $GLOBALS['HTML']->includeFooterJavascriptFile($this->include_assets->getFileURL('siteadmin-gerrit.js'));

        $this->admin_page_renderer->renderANoFramedPresenter(
            $title,
            dirname(GIT_BASE_DIR) . '/templates',
            'admin-plugin',
            $admin_presenter
        );
    }

    private function getManageAllowedProjectsPresenter(Codendi_Request $request)
    {
        $gerrit_server_id = $request->get('gerrit_server_id');
        $gerrit_server    = $this->gerrit_server_factory->getServerById($gerrit_server_id);

        return new AdminAllowedProjectsGerritPresenter(
            $gerrit_server,
            $this->gerrit_ressource_restrictor->searchAllowedProjects($gerrit_server),
            $this->gerrit_ressource_restrictor->isRestricted($gerrit_server)
        );
    }

    private function fetchGerritServers()
    {
        if (empty($this->servers)) {
            $this->servers = $this->gerrit_server_factory->getServers();
        }
    }

    private function getListOfGerritServersPresenters()
    {
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
        if ($this->allGerritServerParamsRequiredExist($request_gerrit_server) && $this->isHTTPPasswordDefined($request_gerrit_server)) {
            $gerrit_server = $this->admin_gerrit_builder->buildFromRequest($request_gerrit_server);
            $server = new Git_RemoteServer_GerritServer(
                0,
                $gerrit_server['host'],
                $gerrit_server['ssh_port'],
                $gerrit_server['http_port'],
                $gerrit_server['login'],
                $gerrit_server['identity_file'],
                $gerrit_server['replication_ssh_key'],
                $gerrit_server['use_ssl'],
                $gerrit_server['gerrit_version'],
                $gerrit_server['http_password'],
                ''
            );

            $this->gerrit_server_factory->save($server);
            $this->servers[$server->getId()] = $server;

            $this->updateReplicationPassword($server, $gerrit_server['replication_password']);
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
                $gerrit_server = $this->admin_gerrit_builder->buildFromRequest($request_gerrit_server);
                if (
                    $gerrit_server['host'] != $server->getHost() ||
                    $gerrit_server['ssh_port'] != $server->getSSHPort() ||
                    $gerrit_server['http_port'] != $server->getHTTPPort() ||
                    $gerrit_server['login'] != $server->getLogin() ||
                    $gerrit_server['identity_file'] != $server->getIdentityFile() ||
                    $gerrit_server['replication_ssh_key'] != $server->getReplicationKey() ||
                    $gerrit_server['use_ssl'] != $server->usesSSL() ||
                    $gerrit_server['gerrit_version'] != $server->getGerritVersion() ||
                    $gerrit_server['http_password'] != $server->getHTTPPassword()
                ) {
                    $server
                        ->setHost($gerrit_server['host'])
                        ->setSSHPort($gerrit_server['ssh_port'])
                        ->setHTTPPort($gerrit_server['http_port'])
                        ->setLogin($gerrit_server['login'])
                        ->setIdentityFile($gerrit_server['identity_file'])
                        ->setReplicationKey($gerrit_server['replication_ssh_key'])
                        ->setUseSSL($gerrit_server['use_ssl'])
                        ->setGerritVersion($gerrit_server['gerrit_version']);

                    if ($gerrit_server['http_password'] !== "") {
                        $server->setHTTPPassword($gerrit_server['http_password']);
                    }

                    $this->gerrit_server_factory->save($server);
                    $this->servers[$server->getId()] = $server;
                }

                $this->updateReplicationPassword($server, $gerrit_server['replication_password']);
            }
        }
    }

    private function updateReplicationPassword(Git_RemoteServer_GerritServer $server, $replication_password)
    {
        if ($replication_password !== "" && ! hash_equals($server->getReplicationPassword(), $replication_password)) {
            $server->setReplicationPassword($replication_password);
            $this->gerrit_server_factory->updateReplicationPassword($server);

            $this->servers[$server->getId()] = $server;
        }
    }

    private function isHTTPPasswordDefined($request_gerrit_server)
    {
        return (isset($request_gerrit_server['http_password']) && ! empty($request_gerrit_server['http_password']));
    }

    private function allGerritServerParamsRequiredExist(array $request_gerrit_server): bool
    {
        return (isset($request_gerrit_server['host']) && ! empty($request_gerrit_server['host'])) &&
        (isset($request_gerrit_server['ssh_port']) && ! empty($request_gerrit_server['ssh_port'])) &&
        (isset($request_gerrit_server['http_port']) && ! empty($request_gerrit_server['http_port'])) &&
        (isset($request_gerrit_server['login']) && ! empty($request_gerrit_server['login'])) &&
        (isset($request_gerrit_server['identity_file']) && ! empty($request_gerrit_server['identity_file']));
    }
}
