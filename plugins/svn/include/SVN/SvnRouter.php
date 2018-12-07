<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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
use Tuleap\SVN\AccessControl\AccessControlController;
use Tuleap\SVN\Admin\AdminController;
use Tuleap\SVN\Admin\GlobalAdminController;
use Tuleap\SVN\Admin\ImmutableTagController;
use Tuleap\SVN\Admin\RestoreController;
use Tuleap\SVN\Explorer\ExplorerController;
use Tuleap\SVN\Explorer\RepositoryDisplayController;
use Tuleap\SVN\PermissionsPerGroup\SVNJSONPermissionsRetriever;
use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVN\Repository\RepositoryManager;
use UGroupManager;

class SvnRouter
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

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var SvnPermissionManager */
    private $permissions_manager;

    /** @var ImmutableTagController */
    private $immutable_tag_controller;

    /** @var GlobalAdminController */
    private $global_admin_controller;

    /** @var RestoreController */
    private $restore_controller;
    /**
     * @var SVNJSONPermissionsRetriever
     */
    private $json_retriever;

    public function __construct(
        RepositoryManager $repository_manager,
        UGroupManager $ugroup_manager,
        SvnPermissionManager $permissions_manager,
        AccessControlController $access_control_controller,
        AdminController $notification_controller,
        ExplorerController $explorer_controller,
        RepositoryDisplayController $display_controller,
        ImmutableTagController $immutable_tag_controller,
        GlobalAdminController $global_admin_controller,
        RestoreController $restore_controller,
        SVNJSONPermissionsRetriever $json_retriever
    ) {
        $this->repository_manager        = $repository_manager;
        $this->permissions_manager       = $permissions_manager;
        $this->ugroup_manager            = $ugroup_manager;
        $this->access_control_controller = $access_control_controller;
        $this->admin_controller          = $notification_controller;
        $this->explorer_controller       = $explorer_controller;
        $this->display_controller        = $display_controller;
        $this->immutable_tag_controller  = $immutable_tag_controller;
        $this->global_admin_controller   = $global_admin_controller;
        $this->restore_controller        = $restore_controller;
        $this->json_retriever            = $json_retriever;
    }

    /**
     * Routes the request to the correct controller
     * @param HTTPRequest $request
     * @return void
     */
    public function route(HTTPRequest $request)
    {
        try {
            $this->useAViewVcRoadIfRootValid($request);

            if (!$request->get('action')) {
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
                    } else if ($request->get('delete-mailing-lists')) {
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
                    if (!$request->getCurrentUser()->isSuperUser()) {
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
                        $this->getService($request),
                        $request
                    );
                    break;
                case 'admin-groups':
                    $this->checkUserCanAdministrateARepository($request);
                    $this->global_admin_controller->showAdminGroups(
                        $this->getService($request),
                        $request
                    );
                    break;
                case 'permission-per-group':
                    if (!$request->getCurrentUser()->isAdmin($request->getProject()->getID())) {
                        $GLOBALS['Response']->send400JSONErrors(
                            array(
                                'error' => dgettext(
                                    'tuleap-svn',
                                    "You don't have permissions to see user groups."
                                )
                            )
                        );
                    }

                    $this->json_retriever->retrieve($request->getProject());
                    break;

                default:
                    $this->useDefaultRoute($request);
                    break;
            }
        } catch (CannotFindRepositoryException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_svn', 'find_error'));
            $GLOBALS['Response']->redirect(SVN_BASE_URL . '/?group_id=' . $request->get('group_id'));
        } catch (UserCannotAdministrateRepositoryException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('global', 'perm_denied'));
            $GLOBALS['Response']->redirect(SVN_BASE_URL . '/?group_id=' . $request->get('group_id'));
        }
    }


    private function checkUserCanAdministrateARepository(HTTPRequest $request)
    {
        if (!$this->permissions_manager->isAdmin($request->getProject(), $request->getCurrentUser())) {
            throw new UserCannotAdministrateRepositoryException();
        }
    }

    private function useAViewVcRoadIfRootValid(HTTPRequest $request)
    {
        if ($request->get('root')) {
            $repository = $this->repository_manager->getRepositoryFromPublicPath($request);

            $request->set("repo_id", $repository->getId());

            $this->display_controller->displayRepository($this->getService($request), $request);

            return;
        }
    }

    /**
     * @param HTTPRequest $request
     */
    private function useDefaultRoute(HTTPRequest $request)
    {
        $this->explorer_controller->index($this->getService($request), $request);
    }

    /**
     * Retrieves the SVN Service instance matching the request group id.
     *
     * @param HTTPRequest $request
     *
     * @return ServiceSvn
     */
    private function getService(HTTPRequest $request)
    {
        $service = $request->getProject()->getService('plugin_svn');

        if ($service === null) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_svn', 'url_can_not_be_processed')
            );
            $GLOBALS['Response']->redirect('/');
        }

        return $request->getProject()->getService('plugin_svn');
    }
}
