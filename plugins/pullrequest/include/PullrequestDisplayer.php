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
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\IncludeCoreAssets;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\PullRequest\MergeSetting\MergeSettingRetriever;
use Tuleap\Request\NotFoundException;

class PullrequestDisplayer
{
    public function __construct(
        private Factory $factory,
        private TemplateRenderer $template_renderer,
        private MergeSettingRetriever $merge_setting_retriever,
        private GitRepositoryHeaderDisplayer $header_displayer,
        private GitRepositoryFactory $repository_factory,
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

        $assets = new IncludeAssets(
            __DIR__ . '/../scripts/pullrequests-app/frontend-assets',
            '/assets/pullrequest/pullrequests-app'
        );

        $layout->addCssAsset(
            new CssAssetWithoutVariantDeclinaisons(
                $assets,
                'pull-requests-style'
            )
        );

        $layout->addJavascriptAsset(
            new JavascriptAsset(
                new IncludeCoreAssets(),
                'syntax-highlight.js'
            )
        );

        $is_vue_overview_shown = $request->get("tab") === "overview";
        if ($is_vue_overview_shown) {
            $layout->addJavascriptAsset(
                new JavascriptViteAsset(
                    new IncludeViteAssets(
                        __DIR__ . '/../scripts/pullrequest-overview/frontend-assets',
                        '/assets/pullrequest/pullrequest-overview'
                    ),
                    'src/index.ts'
                )
            );
        } else {
            $layout->includeFooterJavascriptFile(
                (new JavascriptAsset($assets, 'tuleap-pullrequest.js'))->getFileURL()
            );
        }

        $this->header_displayer->display(
            $request,
            $layout,
            $user,
            $repository
        );

        $presenter = new PullRequestPresenter(
            $repository,
            $user,
            $nb_pull_requests,
            $this->merge_setting_retriever->getMergeSettingForRepository($repository),
            $is_vue_overview_shown
        );

        $this->template_renderer->renderToPage($presenter->getTemplateName(), $presenter);

        $layout->footer([]);

        exit;
    }
}
