<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\SVN;

use Feedback;
use HTTPRequest;
use ProjectManager;
use SvnPlugin;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\SVN\AccessControl\AccessControlController;
use Tuleap\SVN\Admin\AdminController;
use Tuleap\SVN\Admin\GlobalAdministratorsUpdater;
use Tuleap\SVN\Admin\ImmutableTagController;
use Tuleap\SVN\Admin\RestoreController;
use Tuleap\SVN\Explorer\ExplorerController;
use Tuleap\SVN\Explorer\RepositoryDisplayController;
use Tuleap\SVN\PermissionsPerGroup\SVNJSONPermissionsRetriever;
use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVN\Repository\RepositoryManager;

class SvnRouter implements DispatchableWithRequest
{
    /** @var RepositoryDisplayController */
    private $display_controller;

    /** @var ExplorerController */
    private $explorer_controller;

    /** @var AdminController */
    private $admin_controller;

    /** @var AccessControlController */
    private $access_control_controller;

    /** @var RepositoryManager */
    private $repository_manager;

    /** @var SvnPermissionManager */
    private $permissions_manager;

    /** @var ImmutableTagController */
    private $immutable_tag_controller;

    /** @var GlobalAdministratorsUpdater */
    private $global_admin_controller;

    /** @var RestoreController */
    private $restore_controller;
    /**
     * @var SVNJSONPermissionsRetriever
     */
    private $json_retriever;
    /**
     * @var SvnPlugin
     */
    private $plugin;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        RepositoryManager $repository_manager,
        SvnPermissionManager $permissions_manager,
        AccessControlController $access_control_controller,
        AdminController $notification_controller,
        ExplorerController $explorer_controller,
        RepositoryDisplayController $display_controller,
        ImmutableTagController $immutable_tag_controller,
        GlobalAdministratorsUpdater $global_admin_controller,
        RestoreController $restore_controller,
        SVNJSONPermissionsRetriever $json_retriever,
        SvnPlugin $plugin,
        ProjectManager $project_manager,
    ) {
        $this->repository_manager        = $repository_manager;
        $this->permissions_manager       = $permissions_manager;
        $this->access_control_controller = $access_control_controller;
        $this->admin_controller          = $notification_controller;
        $this->explorer_controller       = $explorer_controller;
        $this->display_controller        = $display_controller;
        $this->immutable_tag_controller  = $immutable_tag_controller;
        $this->global_admin_controller   = $global_admin_controller;
        $this->restore_controller        = $restore_controller;
        $this->json_retriever            = $json_retriever;
        $this->plugin                    = $plugin;
        $this->project_manager           = $project_manager;
    }

    /**
     * Routes the request to the correct controller
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        try {
            $this->checkAccessToProject($request, $layout);

            $this->useAViewVcRoadIfRootValid($request, $variables);

            if (! $request->get('action')) {
                $this->useDefaultRoute($request);

                return;
            }

            $action = $request->get('action');

            switch ($action) {
                case "create-repository":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->explorer_controller->createRepository($request, $request->getCurrentUser());
                    break;
                case "settings":
                case "display-mail-notification":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->admin_controller->displayMailNotification($this->getService($request), $request);
                    break;
                case "save-mail-header":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->admin_controller->saveMailHeader($request);
                    break;
                case "update-mailing-lists":
                    if ($request->get('save-mailing-lists')) {
                        $this->checkUserCanAdministrateARepository($request);
                        $this->admin_controller->saveMailingList($request);
                    } elseif ($request->get('delete-mailing-lists')) {
                        $this->checkUserCanAdministrateARepository($request);
                        $this->admin_controller->deleteMailingList($request);
                    }
                    break;
                case "save-hooks-config":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->admin_controller->updateHooksConfig($this->getService($request), $request);
                    break;
                case "hooks-config":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->admin_controller->displayHooksConfig($this->getService($request), $request);
                    break;
                case "display-repository-delete":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->admin_controller->displayRepositoryDelete($this->getService($request), $request);
                    break;
                case "delete-repository":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->admin_controller->deleteRepository($request);
                    break;
                case 'restore':
                    if (! $request->getCurrentUser()->isSuperUser()) {
                        throw new UserCannotAdministrateRepositoryException();
                    }
                    $this->restore_controller->restoreRepository($request);
                    break;
                case "access-control":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->access_control_controller->displayAuthFile($this->getService($request), $request);
                    break;
                case "save-access-file":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->access_control_controller->saveAuthFile($this->getService($request), $request);
                    break;
                case "display-archived-version":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->access_control_controller->displayArchivedVersion($request);
                    break;
                case "display-immutable-tag":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->immutable_tag_controller->displayImmutableTag($this->getService($request), $request);
                    break;
                case "save-immutable-tag":
                    $this->checkUserCanAdministrateARepository($request);
                    $this->immutable_tag_controller->saveImmutableTag($this->getService($request), $request);
                    break;
                case 'save-admin-groups':
                    $this->checkUserCanAdministrateARepository($request);
                    $this->global_admin_controller->saveAdminGroups(
                        $request,
                        $GLOBALS['Response'],
                    );
                    break;
                case 'permission-per-group':
                    if (! $request->getCurrentUser()->isAdmin($request->getProject()->getID())) {
                        $GLOBALS['Response']->send400JSONErrors(
                            [
                                'error' => [
                                    'message' => dgettext(
                                        'tuleap-svn',
                                        "You don't have permissions to see user groups."
                                    ),
                                ],
                            ]
                        );
                    }

                    $this->json_retriever->retrieve($request->getProject());
                    break;

                default:
                    $this->useDefaultRoute($request);
                    break;
            }
        } catch (CannotFindRepositoryException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-svn', 'Repository not found'));
            $GLOBALS['Response']->redirect(SVN_BASE_URL . '/?group_id=' . $request->get('group_id'));
        } catch (UserCannotAdministrateRepositoryException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('global', 'perm_denied'));
            $GLOBALS['Response']->redirect(SVN_BASE_URL . '/?group_id=' . $request->get('group_id'));
        }
    }

    private function checkUserCanAdministrateARepository(HTTPRequest $request)
    {
        if (! $this->permissions_manager->isAdmin($request->getProject(), $request->getCurrentUser())) {
            throw new UserCannotAdministrateRepositoryException();
        }
    }

    private function useAViewVcRoadIfRootValid(HTTPRequest $request, array $url_variables)
    {
        if ($request->get('root')) {
            $repository = $this->repository_manager->getRepositoryFromPublicPath($request);

            $request->set("repo_id", $repository->getId());

            $this->display_controller->displayRepository($this->getService($request), $request, $url_variables);

            return;
        }
    }

    private function useDefaultRoute(HTTPRequest $request)
    {
        $this->explorer_controller->index($this->getService($request), $request);
    }

    /**
     * Retrieves the SVN Service instance matching the request group id.
     *
     *
     * @return ServiceSvn
     */
    private function getService(HTTPRequest $request)
    {
        $service = $request->getProject()->getService('plugin_svn');

        if ($service === null) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-svn', 'The requested action can not be understood, you have been redirected to the homepage')
            );
            $GLOBALS['Response']->redirect('/');
        }

        $service = $request->getProject()->getService('plugin_svn');
        assert($service instanceof ServiceSvn);
        return $service;
    }

    private function getProjectFromViewVcURL(HTTPRequest $request)
    {
        $svn_root = $request->get('root');
        if (strpos($svn_root, '/') !== false) {
            $project_shortname = substr($svn_root, 0, strpos($svn_root, '/'));
        } else {
            $project_shortname = $svn_root;
        }
        return $this->project_manager->getProjectByCaseInsensitiveUnixName($project_shortname);
    }

    /**
     * @throws CannotFindRepositoryException
     */
    private function checkAccessToProject(HTTPRequest $request, BaseLayout $layout): void
    {
        $project = $request->getProject();
        if (! $project->getID()) {
            $project = $this->getProjectFromViewVcURL($request);
        }

        if (! $project) {
            $layout->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('include_group', 'g_not_found')
            );

            $layout->redirect('/');
        } elseif ($project->isDeleted()) {
            $layout->addFeedback(
                Feedback::ERROR,
                _('This project is deleted')
            );

            $layout->redirect('/');
        }

        $project_id = $project->getId();
        $request->set('group_id', $project_id);

        if (! $this->plugin->isAllowed($project_id)) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-svn', 'SVN multi-repositories is disabled for the project.')
            );
            $layout->redirect('/projects/' . $project->getUnixNameMixedCase() . '/');
        }
    }
}
