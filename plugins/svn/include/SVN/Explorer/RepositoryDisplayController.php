<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\SVN\Explorer;

use Event;
use EventManager;
use HTTPRequest;
use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\ServiceSvn;
use Tuleap\SVN\SvnPermissionManager;
use Tuleap\SVN\ViewVC\ViewVCProxy;

class RepositoryDisplayController
{
    /** @var SvnPermissionManager */
    private $permissions_manager;
    /**
     * @var RepositoryManager
     */
    private $repository_manager;
    /**
     * @var \Tuleap\SVN\ViewVC\ViewVCProxy
     */
    private $proxy;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        RepositoryManager $repository_manager,
        SvnPermissionManager $permissions_manager,
        ViewVCProxy $viewvc_proxy,
        EventManager $event_manager
    ) {
        $this->permissions_manager = $permissions_manager;
        $this->repository_manager  = $repository_manager;
        $this->proxy               = $viewvc_proxy;
        $this->event_manager       = $event_manager;
    }

    public function displayRepository(ServiceSvn $service, HTTPRequest $request, array $url_variables)
    {
        try {
            $repository = $this->repository_manager->getByIdAndProject($request->get('repo_id'), $request->getProject());

            $has_plugin_intro  = false;
            $plugin_intro_info = '';
            $this->event_manager->processEvent(Event::SVN_INTRO, array(
                'svn_intro_in_plugin' => &$has_plugin_intro,
                'svn_intro_info'      => &$plugin_intro_info,
                'group_id'            => $repository->getProject()->getID(),
                'user_id'             => $request->getCurrentUser()->getId()
            ));
            $username = $request->getCurrentUser()->getUserName();
            if ($plugin_intro_info) {
                $username = $plugin_intro_info->getLogin();
            }

            $service->renderInPageWithBodyClass(
                $request,
                dgettext('tuleap-svn', 'SVN with multiple repositories'),
                'explorer/repository_display',
                new RepositoryDisplayPresenter(
                    $repository,
                    $request,
                    $this->proxy->getContent($request, $this->fixPathInfo($url_variables)),
                    $this->permissions_manager,
                    $username
                ),
                $this->proxy->getBodyClass()
            );
        } catch (CannotFindRepositoryException $e) {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-svn', 'Repository not found.'));
            $GLOBALS['Response']->redirect(
                SVN_BASE_URL . '/?' . http_build_query(array('group_id' => $request->getProject()->getID()))
            );
        }
    }

    private function fixPathInfo(array $variables) : string
    {
        if (isset($variables['path']) && $variables['path'] !== '') {
            return $this->addTrailingSlash($this->addLeadingSlash($variables['path']));
        }
        return '/';
    }

    private function addLeadingSlash(string $path) : string
    {
        if ($path[0] !== '/') {
            return '/' . $path;
        }
        return $path;
    }

    private function addTrailingSlash(string $path) : string
    {
        if (strrpos($path, "/") !== (strlen($path) - 1)) {
            return $path . '/';
        }
        return $path;
    }
}
