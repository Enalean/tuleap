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
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\layout\ScriptAsset;
use Tuleap\PullRequest\MergeSetting\MergeSettingRetriever;

class PullrequestDisplayer
{
    /**
     * @var Factory
     */
    private $factory;
    /**
     * @var TemplateRenderer
     */
    private $template_renderer;
    /**
     * @var MergeSettingRetriever
     */
    private $merge_setting_retriever;
    /**
     * @var GitRepositoryHeaderDisplayer
     */
    private $header_displayer;
    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    public function __construct(
        Factory $factory,
        TemplateRenderer $template_renderer,
        MergeSettingRetriever $merge_setting_retriever,
        GitRepositoryHeaderDisplayer $header_displayer,
        GitRepositoryFactory $repository_factory
    ) {
        $this->factory                 = $factory;
        $this->template_renderer       = $template_renderer;
        $this->merge_setting_retriever = $merge_setting_retriever;
        $this->header_displayer        = $header_displayer;
        $this->repository_factory      = $repository_factory;
    }

    public function display(\HTTPRequest $request, BaseLayout $layout)
    {
        $repository = $this->repository_factory->getRepositoryById($request->getValidated('repo_id', 'uint', 0));
        if ($repository) {
            $nb_pull_requests = $this->factory->getPullRequestCount($repository);
            $user             = $request->getCurrentUser();

            $GLOBALS['HTML'] = $GLOBALS['Response'] = $layout;

            $assets = new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/pullrequest',
                '/assets/pullrequest'
            );

            $layout->addCssAsset(
                new CssAsset(
                    $assets,
                    'pull-requests'
                )
            );

            $this->header_displayer->display(
                $request,
                $layout,
                $user,
                $repository
            );



            $pull_requests_app = new ScriptAsset($assets, 'tuleap-pullrequest.js');
            $layout->includeFooterJavascriptFile($pull_requests_app->getFileURL());

            $presenter = new PullRequestPresenter(
                $repository->getId(),
                $user->getId(),
                $user->getShortLocale(),
                $nb_pull_requests,
                $this->merge_setting_retriever->getMergeSettingForRepository($repository)
            );

            $this->template_renderer->renderToPage($presenter->getTemplateName(), $presenter);

            $layout->footer([]);
            exit;
        } else {
            throw new \Tuleap\Request\NotFoundException();
        }
    }
}
