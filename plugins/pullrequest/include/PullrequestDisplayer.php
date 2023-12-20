<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use GitRepositoryFactory;
use TemplateRenderer;
use Tuleap\Git\Repository\GitRepositoryHeaderDisplayer;
use Tuleap\Layout\BaseLayout;
use Tuleap\PullRequest\FrontendApps\PullRequestAppsLoader;
use Tuleap\PullRequest\FrontendApps\PullRequestApp;
use Tuleap\PullRequest\MergeSetting\MergeSettingRetriever;
use Tuleap\Request\NotFoundException;

class PullrequestDisplayer
{
    public function __construct(
        private readonly Factory $factory,
        private readonly TemplateRenderer $template_renderer,
        private readonly MergeSettingRetriever $merge_setting_retriever,
        private readonly GitRepositoryHeaderDisplayer $header_displayer,
        private readonly GitRepositoryFactory $repository_factory,
        private readonly PullRequestEmptyStatePresenterBuilder $empty_state_presenter_builder,
    ) {
    }

    public function display(\HTTPRequest $request, BaseLayout $layout): void
    {
        $repository = $this->repository_factory->getRepositoryById($request->getValidated('repo_id', 'uint', 0));
        if (! $repository) {
            throw new NotFoundException();
        }

        $nb_pull_requests = $this->factory->getPullRequestCount($repository);
        $user             = $request->getCurrentUser();

        $GLOBALS['HTML'] = $GLOBALS['Response'] = $layout;

        $app_to_load = PullRequestApp::fromRequest($request);

        if ($app_to_load === PullRequestApp::HOMEPAGE_APP && ! $nb_pull_requests->isThereAtLeastOnePullRequest()) {
            $presenter = $this->empty_state_presenter_builder->build(
                $repository,
                $user,
            );
        } else {
            PullRequestAppsLoader::loadPullRequestApps(
                $layout,
                $app_to_load,
            );

            $presenter = new PullRequestPresenter(
                $repository,
                $user,
                $nb_pull_requests,
                $this->merge_setting_retriever->getMergeSettingForRepository($repository),
                $app_to_load
            );
        }

        $this->header_displayer->display(
            $request,
            $layout,
            $user,
            $repository
        );

        $this->template_renderer->renderToPage($presenter->getTemplateName(), $presenter);

        $layout->footer([]);

        exit;
    }
}
