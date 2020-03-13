<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\RemoteServer\Gerrit;

use Codendi_Request;
use ProjectManager;
use CSRFSynchronizerToken;
use Git_RemoteServer_GerritServer;
use Tuleap\Git\GerritServerResourceRestrictor;
use Git_RemoteServer_GerritServerFactory;
use Feedback;
use Git_RemoteServer_NotFoundException;

class Restrictor
{

    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $gerrit_server_factory;

    /**
     * @var GerritServerResourceRestrictor
     */
    private $gerrit_ressource_restrictor;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        GerritServerResourceRestrictor $gerrit_ressource_restrictor,
        ProjectManager $project_manager
    ) {
        $this->gerrit_server_factory       = $gerrit_server_factory;
        $this->gerrit_ressource_restrictor = $gerrit_ressource_restrictor;
        $this->project_manager             = $project_manager;
    }

    public function setGerritServerRestriction(Codendi_Request $request)
    {
        $gerrit_server = $this->getGerritServerFromRequest($request);

        $this->checkSynchronizerToken(
            GIT_SITE_ADMIN_BASE_URL . '?view=gerrit_servers_restriction&action=set-gerrit-server-restriction&gerrit_server_id=' .
            urlencode($gerrit_server->getId())
        );

        $this->restrictGerritServer($request, $gerrit_server);

        $GLOBALS['Response']->redirect(
            GIT_SITE_ADMIN_BASE_URL . '?view=gerrit_servers_restriction&action=manage-allowed-projects&gerrit_server_id=' .
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
                dgettext('tuleap-git', 'Now, only the allowed projects are able to use this Gerrit server. Projects with at least one repository using this Gerrit server have been automatically added to the list of allowed projects. This restriction is not yet taken into account.')
            );
        }
    }

    private function unsetRestriction(Git_RemoteServer_GerritServer $gerrit_server)
    {
        if ($this->gerrit_ressource_restrictor->unsetRestriction($gerrit_server)) {
            $GLOBALS['Response']->addFeedback(
                'info',
                dgettext('tuleap-git', 'All projects can now use this Gerrit server.')
            );
        }
    }

    /**
     * @psalm-return never-return
     */
    private function redirectToGerritServerList(): void
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-git', 'The requested Gerrit server does not exist.')
        );

        $GLOBALS['Response']->redirect(GIT_SITE_ADMIN_BASE_URL . '?pane=gerrit_servers_admin');
        exit();
    }

    private function checkSynchronizerToken($url)
    {
        $token = new CSRFSynchronizerToken($url);
        $token->check();
    }

    /**
     * @return Git_RemoteServer_GerritServer
     */
    private function getGerritServerFromRequest(Codendi_Request $request)
    {
        $gerrit_server_id = $request->get('gerrit_server_id');

        try {
            $gerrit_server = $this->gerrit_server_factory->getServerById($gerrit_server_id);
        } catch (Git_RemoteServer_NotFoundException $exception) {
            $this->redirectToGerritServerList();
        }

        return $gerrit_server;
    }

    public function updateAllowedProjectList(Codendi_Request $request)
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
            GIT_SITE_ADMIN_BASE_URL . '?view=gerrit_servers_restriction&action=manage-allowed-projects&gerrit_server_id=' .
            urlencode($gerrit_server->getId())
        );
    }

    private function allowProjectForGerritServer(Git_RemoteServer_GerritServer $gerrit_server, $project_to_add)
    {
        $project = $this->project_manager->getProjectFromAutocompleter($project_to_add);

        if ($project && $this->gerrit_ressource_restrictor->allowProject($gerrit_server, $project)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-git', 'Submitted project can now use this Gerrit server.')
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-git', 'Something went wrong during the update of the allowed project list.')
            );
        }
    }

    private function revokeProjectsForGerritServer(Git_RemoteServer_GerritServer $gerrit_server, $project_ids)
    {
        $unset_project_ids = array();
        foreach ($project_ids as $key => $project_id) {
            if ($this->gerrit_server_factory->isServerUsedInProject($gerrit_server, $project_id)) {
                $unset_project_ids[] = $project_id;
                unset($project_ids[$key]);
            }
        }

        if (count($unset_project_ids) > 0) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(dgettext('tuleap-git', 'The following projects were not revoked because these projects use this Gerrit server: %1$s'), implode(',', $unset_project_ids))
            );
        }

        if (count($project_ids) > 0) {
            if ($this->gerrit_ressource_restrictor->revokeProject($gerrit_server, $project_ids)) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    dgettext('tuleap-git', 'Submitted projects will not be able to use this Gerrit server.')
                );
            } else {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    dgettext('tuleap-git', 'Something went wrong during the update of the allowed project list.')
                );
            }
        }
    }
}
