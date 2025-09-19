<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use EventManager;
use Git_URL;
use GitPlugin;
use GitRepository;
use GitViews_GitPhpViewer;
use GitViews_ShowRepo_Content;
use HTTPRequest;
use Project;
use TemplateRendererFactory;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyLegacyBool;
use Tuleap\Event\Events\ProjectProviderEvent;
use Tuleap\Git\Repository\GitRepositoryHeaderDisplayer;
use Tuleap\Git\Repository\View\FilesHeaderPresenterBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\User\ProvideCurrentUserWithLoggedInInformation;

#[ConfigKeyCategory('Git')]
final readonly class GitRepositoryBrowserController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    #[ConfigKey('Allow anonymous users to browse git repositories (useful to protect from bot scrapping)')]
    #[ConfigKeyLegacyBool(true)]
    public const string CONFIG_IS_ANONYMOUS_GIT_BROWSING_ALLOWED = 'git_anonymous_browsing_allowed';

    public function __construct(
        private \GitRepositoryFactory $repository_factory,
        private \ProjectManager $project_manager,
        private History\GitPhpAccessLogger $access_logger,
        private GitRepositoryHeaderDisplayer $header_displayer,
        private FilesHeaderPresenterBuilder $files_header_presenter_builder,
        private ProvideCurrentUserWithLoggedInInformation $current_user_provider,
        private EventManager $event_manager,
    ) {
    }

    /**
     *
     * @throws NotFoundException
     */
    #[\Override]
    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProjectByCaseInsensitiveUnixName($variables['project_name']);
        if (! $project || $project->isError()) {
            throw new NotFoundException(dgettext('tuleap-git', 'Project not found.'));
        }

        return $project;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     *
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);
        if (! $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext('tuleap-git', 'Git service is disabled.'));
        }

        $repository = $this->repository_factory->getByProjectNameAndPath(
            $variables['project_name'],
            $variables['path'] . '.git'
        );
        if (! $repository) {
            throw new NotFoundException('Repository does not exist');
        }

        $current_user = $request->getCurrentUser();

        if (\ForgeConfig::getStringAsBool(self::CONFIG_IS_ANONYMOUS_GIT_BROWSING_ALLOWED) === false && $current_user->isAnonymous()) {
            throw new ForbiddenException('Anonymous browsing is currently forbidden due to rogue AI bot scrapping. Please login to see the repository content.');
        }

        if (! $repository->userCanRead($current_user)) {
            throw new ForbiddenException();
        }

        $this->redirectOutdatedActions($request, $layout);

        \Tuleap\Project\ServiceInstrumentation::increment('git');

        $event = new ProjectProviderEvent($project);
        $this->event_manager->processEvent($event);

        $git_php_viewer = new GitViews_GitPhpViewer($repository, $this->current_user_provider);

        $url = $this->getURL($request, $repository);
        if ($url->isADownload($request)) {
            $git_php_viewer->displayContentWithoutEnclosingDiv();

            return;
        }

        $renderer = TemplateRendererFactory::build()->getRenderer(GIT_TEMPLATE_DIR);

        ob_start();
        $view = new GitViews_ShowRepo_Content(
            $repository,
            $git_php_viewer,
            $request,
            $this->access_logger
        );
        $view->display();
        $gitphp_content = ob_get_clean();

        $this->header_displayer->display($request, $layout, $current_user, $repository);
        $renderer->renderToPage(
            'repository/gitphp/header',
            $this->files_header_presenter_builder->build($request, $repository)
        );

        echo $gitphp_content;

        $renderer->renderToPage('repository/gitphp/footer', []);
        $layout->footer([]);
    }

    private function addUrlParametersToRequest(HTTPRequest $request, Git_URL $url)
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

        $response->permanentRedirect(($parsed_url['path'] ?? '') . '?' . http_build_query($query_parameters));
    }

    /**
     *
     * @return Git_URL
     */
    private function getURL(HTTPRequest $request, GitRepository $repository)
    {
        $url = new Git_URL(
            $this->project_manager,
            $this->repository_factory,
            $_SERVER['REQUEST_URI']
        );

        $request->set('action', 'view');
        $request->set('group_id', $repository->getProjectId());
        $request->set('repo_id', $repository->getId());

        $this->addUrlParametersToRequest($request, $url);

        return $url;
    }
}
