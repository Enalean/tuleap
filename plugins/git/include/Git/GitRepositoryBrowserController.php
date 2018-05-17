<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Git;

use HTTPRequest;
use Tuleap\Git\GitViews\GitViewHeader;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class GitRepositoryBrowserController implements DispatchableWithRequest
{
    /**
     * @var \GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var \Git_RemoteServer_GerritServerFactory
     */
    private $gerrit_server_factory;
    /**
     * @var \Git_GitRepositoryUrlManager
     */
    private $url_manager;
    /**
     * @var \Git_Driver_Gerrit_GerritDriverFactory
     */
    private $gerrit_driver_factory;
    /**
     * @var \Git_Driver_Gerrit_UserAccountManager
     */
    private $gerrit_usermanager;
    /**
     * @var \Git_Mirror_MirrorDataMapper
     */
    private $mirror_data_mapper;
    /**
     * @var \Tuleap\Git\History\GitPhpAccessLogger
     */
    private $access_loger;
    /**
     * @var \GitPermissionsManager
     */
    private $permissions_manager;
    private $gitphp_path;
    private $master_location_name;

    public function __construct(
        \GitRepositoryFactory $repository_factory,
        \ProjectManager $project_manager,
        \Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        \Git_GitRepositoryUrlManager $url_manager,
        \Git_Driver_Gerrit_GerritDriverFactory $gerrit_driver_factory,
        \Git_Driver_Gerrit_UserAccountManager $gerrit_usermanager,
        \Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        \Tuleap\Git\History\GitPhpAccessLogger $access_loger,
        \GitPermissionsManager $permissions_manager,
        $gitphp_path,
        $master_location_name
    ) {
        $this->repository_factory = $repository_factory;
        $this->project_manager    = $project_manager;
        $this->gerrit_server_factory = $gerrit_server_factory;
        $this->url_manager = $url_manager;
        $this->gerrit_driver_factory = $gerrit_driver_factory;
        $this->gerrit_usermanager = $gerrit_usermanager;
        $this->mirror_data_mapper = $mirror_data_mapper;
        $this->access_loger = $access_loger;
        $this->permissions_manager = $permissions_manager;
        $this->gitphp_path = $gitphp_path;
        $this->master_location_name = $master_location_name;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout $layout
     * @param array $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Instrument\Collect::increment(\GitPlugin::INSTRUMENTATION_KEY);

        $repository = $this->repository_factory->getByProjectNameAndPath($variables['project_name'], $variables['path'].'.git');
        if (! $repository) {
            throw new NotFoundException("Repository does not exist");
        }

        $url = new \Git_URL(
            $this->project_manager,
            $this->repository_factory,
            $_SERVER['REQUEST_URI']
        );

        $request->set('action', 'view');
        $request->set('group_id', $repository->getProjectId());
        $request->set('repo_id', $repository->getId());

        $this->addUrlParametersToRequest($request, $url);

        $index_view = new \GitViews_ShowRepo(
            $repository,
            $this->url_manager,
            $request,
            $this->gerrit_driver_factory,
            $this->gerrit_usermanager,
            $this->gerrit_server_factory->getServers(),
            $this->mirror_data_mapper,
            $this->access_loger,
            $this->permissions_manager,
            $this->gitphp_path,
            $this->master_location_name
        );

        $headers = new GitViewHeader(\EventManager::instance(), $this->permissions_manager);

        if (! $url->isADownload($request)) {
            $headers->header($request, $request->getCurrentUser(), $layout, $repository->getProject());
        }

        $index_view->display($url);

        if (! $url->isADownload($request)) {
            $layout->footer([]);
        }
    }

    private function addUrlParametersToRequest(HTTPRequest $request, \Git_URL $url)
    {
        $url_parameters_as_string = $url->getParameters();
        if (! $url_parameters_as_string) {
            return;
        }

        parse_str($url_parameters_as_string, $_GET);
        parse_str($url_parameters_as_string, $_REQUEST);

        parse_str($url_parameters_as_string, $url_parameters);
        foreach ($url_parameters as $key => $value) {
            $request->set($key, $value);
        }
    }
}
