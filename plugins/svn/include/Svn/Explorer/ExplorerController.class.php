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

namespace Tuleap\Svn\Explorer;

use Project;
use Tuleap\Svn\ServiceSvn;
use Tuleap\Svn\Dao;
use CSRFSynchronizerToken;
use \Tuleap\Svn\Repository\RuleName;
use \Tuleap\Svn\Repository\Repository;
use \Tuleap\Svn\Repository\RepositoryManager;
use HTTPRequest;
use \Tuleap\Svn\Repository\CannotCreateRepositoryException;
use SystemEventManager;
use SystemEvent;
use Tuleap\Svn\EventRepository\SystemEvent_SVN_CREATE_REPOSITORY;

class ExplorerController {
    const NAME = 'explorer';

    private $repository_manager;
    private $system_event_manager;

    public function __construct(RepositoryManager $repository_manager) {
        $this->repository_manager   = $repository_manager;
        $this->system_event_manager = SystemEventManager::instance();
    }

    public function index(ServiceSvn $service, HTTPRequest $request) {
        $this->renderIndex($service, $request);
    }

    private function renderIndex(ServiceSvn $service, HTTPRequest $request) {
        $project = $request->getProject();
        $token = $this->generateTokenForCeateRepository($request->getProject());

        $service->renderInPage(
            $request,
            'Welcome',
            'explorer/index',
            new ExplorerPresenter(
                    $request->getCurrentUser(),
                    $project,
                    $token,
                    $request->get('name'),
                    $this->repository_manager
                )
        );
    }

    private function generateTokenForCeateRepository(Project $project) {
        return new CSRFSynchronizerToken(SVN_BASE_URL."/?group_id=".$project->getid(). '&action=createRepo');
    }

    public function createRepository(ServiceSvn $service, HTTPRequest $request) {
        $token = $this->generateTokenForCeateRepository($request->getProject());
        $token->check();

        $rule = new RuleName($request->getProject(), new DAO());
        $repo_name = $request->get("repo_name");

        if (! $rule->isValid($repo_name)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn_manage_repository','invalid_name'));
            $GLOBALS['Response']->addFeedback('error', $rule->getErrorMessage());
            $GLOBALS['Response']->redirect(SVN_BASE_URL.'/?'. http_build_query(array('group_id' => $request->getProject()->getid(), 'name' =>$repo_name)));
        } else {
            $repository_to_create = new Repository ("", $repo_name, $request->getProject());
            try {
                $this->repository_manager->create($repository_to_create);

                $repo_event['system_path'] = $repository_to_create->getSystemPath();
                $repo_event['project_id']  = $request->getProject()->getId();
                $repo_event['name']        = $request->getProject()->getUnixNameMixedCase()."/".$repository_to_create->getName();
                $this->system_event_manager->createEvent(
                    'Tuleap\\Svn\\EventRepository\\'.SystemEvent_SVN_CREATE_REPOSITORY::NAME,
                    implode(SystemEvent::PARAMETER_SEPARATOR, $repo_event),
                    SystemEvent::PRIORITY_HIGH);

                $GLOBALS['Response']->addFeedback('info', $repo_name.' '.$GLOBALS['Language']->getText('plugin_svn_manage_repository','update_success'));
                $GLOBALS['Response']->redirect(SVN_BASE_URL.'/?'. http_build_query(array('group_id' => $request->getProject()->getid())));
            } catch (CannotCreateRepositoryException $e) {
                $GLOBALS['Response']->addFeedback('error', $repo_name.' '.$GLOBALS['Language']->getText('plugin_svn','update_error'));
                $GLOBALS['Response']->redirect(SVN_BASE_URL.'/?'. http_build_query(array('group_id' => $request->getProject()->getid(), 'name' =>$repo_name)));
            }
        }
    }
}