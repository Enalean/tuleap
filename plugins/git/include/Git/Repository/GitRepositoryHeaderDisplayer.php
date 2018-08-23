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
 */

namespace Tuleap\Git\Repository;

use GitRepository;
use HTTPRequest;
use PFUser;
use TemplateRendererFactory;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Git\GitViews\ShowRepo\RepoHeader;
use Tuleap\Git\Repository\View\RepositoryHeaderPresenterBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;

class GitRepositoryHeaderDisplayer
{
    /**
     * @var HeaderRenderer
     */
    private $header_renderer;
    /**
     * @var RepositoryHeaderPresenterBuilder
     */
    private $header_presenter_builder;
    /**
     * @var IncludeAssets
     */
    private $include_assets;

    public function __construct(
        HeaderRenderer $header_renderer,
        RepositoryHeaderPresenterBuilder $header_presenter_builder,
        IncludeAssets $include_assets
    ) {
        $this->header_renderer          = $header_renderer;
        $this->header_presenter_builder = $header_presenter_builder;
        $this->include_assets           = $include_assets;
    }

    public function display(
        HTTPRequest $request,
        BaseLayout $layout,
        PFUser $current_user,
        GitRepository $repository,
        RepoHeader $repo_header
    ) {
        if (\ForgeConfig::get('git_repository_bp')) {
            $this->includeAssetsForBurningParrot($layout);
            $this->displayForBurningParrot($request, $current_user, $repository);
        } else {
            $this->displayForFlamingParrot($request, $layout, $repository, $repo_header);
        }
    }

    private function includeAssetsForBurningParrot(BaseLayout $layout)
    {
        $layout->addCssAsset(
            new CssAsset(
                new IncludeAssets(
                    __DIR__ . '/../../../www/themes/BurningParrot/assets',
                    GIT_BASE_URL . '/themes/BurningParrot/assets'
                ),
                'git'
            )
        );
        $layout->includeFooterJavascriptFile($this->include_assets->getFileURL('repository.js'));
    }

    private function displayForBurningParrot(
        HTTPRequest $request,
        PFUser $current_user,
        GitRepository $repository
    ) {
        $this->header_renderer->renderRepositoryHeader($request, $current_user, $repository->getProject(), $repository);

        $renderer         = TemplateRendererFactory::build()->getRenderer(GIT_TEMPLATE_DIR);
        $header_presenter = $this->header_presenter_builder->build($repository, $current_user);
        $renderer->renderToPage('repository/header', $header_presenter);
    }

    private function displayForFlamingParrot(
        HTTPRequest $request,
        BaseLayout $layout,
        GitRepository $repository,
        RepoHeader $repo_header
    ) {
        $repo_header->display($request, $layout, $repository);
    }
}
