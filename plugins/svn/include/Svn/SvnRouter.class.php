<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

namespace Tuleap\Svn;

use HTTPRequest;
use Tuleap\Svn\Explorer\ExplorerController;
use Tuleap\Svn\Explorer\RepositoryDisplayController;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\CannotFindRepositoryException;
use Tuleap\Svn\Admin\MailNotificationController;
use Tuleap\Svn\Admin\AdminController;
use Tuleap\Svn\AuthFile\AccessFileHistoryManager;
use Tuleap\Svn\Admin\MailHeaderManager;
use Tuleap\Svn\Admin\MailnotificationManager;
use ProjectManager;
use Project;
use ForgeConfig;


class SvnRouter {

    private $repository_manager;
    private $project_manager;
    private $mail_header_manager;
    private $mail_notification_manager;

    public function __construct(
            RepositoryManager $repository_manager,
            ProjectManager $project_manager,
            MailHeaderManager $mail_header_manager,
            MailnotificationManager $mail_notification_manager
        ) {
        $this->repository_manager        = $repository_manager;
        $this->project_manager           = $project_manager;
        $this->mail_header_manager       = $mail_header_manager;
        $this->mail_notification_manager = $mail_notification_manager;
    }

    /**
     * Routes the request to the correct controller
     * @param HTTPRequest $request
     * @return void
     */
    public function route(HTTPRequest $request) {

        $this->useAViewVcRoadIfRootValid($request);

        if (! $request->get('action')) {
            $this->useDefaultRoute($request);
            return;
        }

        $action = $request->get('action');

        switch ($action) {
            case "create-repository":
                $controller = new ExplorerController($this->repository_manager);
                $controller->createRepository($this->getService($request), $request);
                break;
            case "display-repository":
                $controller = new RepositoryDisplayController($this->repository_manager, $this->project_manager);
                $controller->displayRepository($this->getService($request), $request);
                break;
            case "display-mail-notification":
                $controller = new MailNotificationController($this->mail_header_manager, $this->repository_manager, $this->mail_notification_manager);
                $controller->displayMailNotification($this->getService($request), $request);
                break;
            case "save-mail-header":
                $controller = new MailNotificationController($this->mail_header_manager, $this->repository_manager, $this->mail_notification_manager);
                $controller->saveMailHeader($request);
                break;
            case "create-mailing-lists":
                $controller = new MailNotificationController($this->mail_header_manager, $this->repository_manager, $this->mail_notification_manager);
                $controller->createMailingList($request);
                break;
            case "delete-mailing-list":
                $controller = new MailNotificationController($this->mail_header_manager, $this->repository_manager, $this->mail_notification_manager);
                $controller->deleteMailingList($request);
                break;
            default:
                $this->useDefaultRoute($request);
                break;
        }
    }

    private function useAViewVcRoadIfRootValid(HTTPRequest $request) {
        try {
            if ($request->get('root')) {

                $repository = $this->repository_manager->getRepositoryAndProjectFromPublicPath($request->get('root'));

                $request->set("group_id", $repository->getProject()->getId());
                $request->set("repo_id", $repository->getId());

                $this->useViewVcRoute($request);
                return;
            }
        } catch (CannotFindRepositoryException $e) {
            $GLOBALS['Response']->addFeedback('info', $request->get('root'). " " .$GLOBALS['Language']->getText('plugin_svn','find_error'));
            $GLOBALS['Response']->redirect('/');
        }
    }

    /**
     * @param HTTPRequest $request
     */
    private function useViewVcRoute(HTTPRequest $request) {
        $controller = new RepositoryDisplayController($this->repository_manager, $this->project_manager);
        $controller->displayRepository($this->getService($request), $request);
    }

    /**
     * @param HTTPRequest $request
     */
    private function useDefaultRoute(HTTPRequest $request) {
        $controller = new ExplorerController($this->repository_manager);
        $controller->index( $this->getService($request), $request );
    }

    /**
     * Retrieves the SVN Service instance matching the request group id.
     *
     * @param HTTPRequest $request
     *
     * @return ServiceSvn
     */
    private function getService(HTTPRequest $request) {
        return $request->getProject()->getService('plugin_svn');
    }
}
