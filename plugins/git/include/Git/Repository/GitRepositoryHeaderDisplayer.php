<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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
use Tuleap\Git\Repository\View\RepositoryHeaderPresenterBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
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
    /**
     * @var \EventManager
     */
    private $event_manager;

    public function __construct(
        HeaderRenderer $header_renderer,
        RepositoryHeaderPresenterBuilder $header_presenter_builder,
        IncludeAssets $include_assets,
        \EventManager $event_manager
    ) {
        $this->header_renderer          = $header_renderer;
        $this->header_presenter_builder = $header_presenter_builder;
        $this->include_assets           = $include_assets;
        $this->event_manager            = $event_manager;
    }

    public function display(
        HTTPRequest $request,
        BaseLayout $layout,
        PFUser $current_user,
        GitRepository $repository
    ) {
        $this->includeAssetsForBurningParrot($request, $layout);
        $this->displayForBurningParrot($request, $current_user, $repository);
    }

    private function includeAssetsForBurningParrot(HTTPRequest $request, BaseLayout $layout) : void
    {
        if (in_array($request->get('a'), ['blob', 'blame'], true)) {
            $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->include_assets, 'syntax-highlight'));
        }
        $layout->addCssAsset(new CssAsset($this->include_assets, 'bp-style'));
        $layout->includeFooterJavascriptFile($this->include_assets->getFileURL('repository.js'));

        $external_assets = new CollectAssets();
        $this->event_manager->processEvent($external_assets);
        foreach ($external_assets->getStylesheets() as $css_asset) {
            $layout->addCssAsset($css_asset);
        }
        foreach ($external_assets->getScripts() as $script) {
            $layout->includeFooterJavascriptFile($script->getFileURL());
        }
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
}
