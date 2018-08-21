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

use GitPlugin;
use HTTPRequest;
use TemplateRendererFactory;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Git\Repository\View\RepositoryHeaderPresenterBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class GitRepositoryBrowserController implements DispatchableWithRequest, DispatchableWithProject
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
    /**
     * @var HeaderRenderer
     */
    private $header_renderer;
    /**
     * @var RepositoryHeaderPresenterBuilder
     */
    private $header_presenter_builder;
    /**
     * @var \ThemeManager
     */
    private $theme_manager;
    /**
     * @var IncludeAssets
     */
    private $include_assets;

    public function __construct(
        \GitRepositoryFactory $repository_factory,
        \ProjectManager $project_manager,
        \Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        History\GitPhpAccessLogger $access_logger,
        GitViews\ShowRepo\RepoHeader $repo_header,
        \ThemeManager $theme_manager,
        HeaderRenderer $header_renderer,
        RepositoryHeaderPresenterBuilder $header_presenter_builder,
        IncludeAssets $include_assets
    ) {
        $this->repository_factory       = $repository_factory;
        $this->project_manager          = $project_manager;
        $this->mirror_data_mapper       = $mirror_data_mapper;
        $this->access_logger            = $access_logger;
        $this->repo_header              = $repo_header;
        $this->theme_manager            = $theme_manager;
        $this->header_renderer          = $header_renderer;
        $this->header_presenter_builder = $header_presenter_builder;
        $this->include_assets           = $include_assets;
    }

    /**
     * @param HTTPRequest $request
     * @param array $variables
     * @return \Project
     * @throws NotFoundException
     */
    public function getProject(HTTPRequest $request, array $variables)
    {
        $project = $this->project_manager->getProjectByCaseInsensitiveUnixName($variables['project_name']);
        if (! $project || $project->isError()) {
            throw new NotFoundException(dgettext("tuleap-git", "Project not found."));
        }

        return $project;
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
        if (\ForgeConfig::get('git_repository_bp')) {
            $layout          = $this->theme_manager->getBurningParrot($request->getCurrentUser());
            $GLOBALS['HTML'] = $GLOBALS['Response'] = $layout;
        }
        $project = $this->getProject($request, $variables);
        if (! $project->usesService(gitPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext("tuleap-git", "Git service is disabled."));
        }

        $repository = $this->repository_factory->getByProjectNameAndPath($variables['project_name'], $variables['path'].'.git');
        if (! $repository) {
            throw new NotFoundException("Repository does not exist");
        }

        $current_user = $request->getCurrentUser();
        if (! $repository->userCanRead($current_user)) {
            throw new ForbiddenException();
        }

        $this->redirectOutdatedActions($request, $layout);

        \Tuleap\Project\ServiceInstrumentation::increment('git');

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
            if (\ForgeConfig::get('git_repository_bp')) {
                $layout->addCssAsset(
                    new CssAsset(
                        new IncludeAssets(
                            __DIR__ . '/../../www/themes/BurningParrot/assets',
                            GIT_BASE_URL . '/themes/BurningParrot/assets'
                        ),
                        'git'
                    )
                );
                $layout->includeFooterJavascriptFile($this->include_assets->getFileURL('repository.js'));
                $this->header_renderer->renderRepositoryHeader($request, $current_user, $project, $repository);

                $renderer         = TemplateRendererFactory::build()->getRenderer(GIT_TEMPLATE_DIR);
                $header_presenter = $this->header_presenter_builder->build($repository, $current_user);
                $renderer->renderToPage('repository/header', $header_presenter);
            } else {
                $this->repo_header->display($request, $layout, $repository);
            }
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

        if (! isset($query_parameters['a'])) {
            return;
        }

        switch ($query_parameters['a']) {
            case 'summary':
                $query_parameters['a'] = 'tree';
                break;
            case 'log':
                $query_parameters['a'] = 'shortlog';
                break;
            default:
                return;
        }

        $response->permanentRedirect($parsed_url['path'] . '?' . http_build_query($query_parameters));
    }
}
