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
     * @var \Git_Mirror_MirrorDataMapper
     */
    private $mirror_data_mapper;
    /**
     * @var History\GitPhpAccessLogger
     */
    private $access_logger;
    /**
     * @var GitViews\ShowRepo\RepoHeader
     */
    private $repo_header;

    public function __construct(
        \GitRepositoryFactory $repository_factory,
        \ProjectManager $project_manager,
        \Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        History\GitPhpAccessLogger $access_logger,
        GitViews\ShowRepo\RepoHeader $repo_header
    ) {
        $this->repository_factory  = $repository_factory;
        $this->project_manager     = $project_manager;
        $this->mirror_data_mapper  = $mirror_data_mapper;
        $this->access_logger       = $access_logger;
        $this->repo_header         = $repo_header;
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
        \Tuleap\Project\ServiceInstrumentation::increment('git');

        $repository = $this->repository_factory->getByProjectNameAndPath($variables['project_name'], $variables['path'].'.git');
        if (! $repository) {
            throw new NotFoundException("Repository does not exist");
        }

        if (! $repository->userCanRead($request->getCurrentUser())) {
            throw new ForbiddenException();
        }

        $this->redirectOutdatedActions($request, $layout);

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
            $request,
            $this->mirror_data_mapper,
            $this->access_logger
        );


        if (! $url->isADownload($request)) {
            $this->repo_header->display($request, $layout, $repository);
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

    private function redirectOutdatedActions(HTTPRequest $request, \Response $response)
    {
        $parsed_url = parse_url($request->getFromServer('REQUEST_URI'));

        if (! isset($parsed_url['query'])) {
            return;
        }

        parse_str($parsed_url['query'], $query_parameters);

        if (! isset($query_parameters['a']) || $query_parameters['a'] !== 'summary') {
            return;
        }

        $query_parameters['a'] = 'tree';
        $response->permanentRedirect($parsed_url['path'] . '?' . http_build_query($query_parameters));
    }
}
