<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ForumML\OneThread;

use HTTPRequest;
use Project;
use Tuleap\date\RelativeDatesAssetsRetriever;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\ForumML\CurrentListBreadcrumbCollectionBuilder;
use Tuleap\ForumML\ListInfoFromVariablesProvider;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

class OneThreadController implements DispatchableWithBurningParrot, DispatchableWithRequest, DispatchableWithProject
{
    /**
     * @var \TemplateRenderer
     */
    private $renderer;
    /**
     * @var IncludeAssets
     */
    private $include_assets;
    /**
     * @var TlpRelativeDatePresenterBuilder
     * /**
     * @var OneThreadPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var CurrentListBreadcrumbCollectionBuilder
     */
    private $breadcrumb_collection_builder;
    /**
     * @var ListInfoFromVariablesProvider
     */
    private $list_info_from_variable_provider;

    public function __construct(
        \TemplateRenderer $renderer,
        IncludeAssets $include_assets,
        OneThreadPresenterBuilder $presenter_builder,
        CurrentListBreadcrumbCollectionBuilder $breadcrumb_collection_builder,
        ListInfoFromVariablesProvider $list_info_from_variable_provider,
    ) {
        $this->renderer                         = $renderer;
        $this->include_assets                   = $include_assets;
        $this->presenter_builder                = $presenter_builder;
        $this->breadcrumb_collection_builder    = $breadcrumb_collection_builder;
        $this->list_info_from_variable_provider = $list_info_from_variable_provider;
    }

    public function getProject(array $variables): Project
    {
        return $this->list_info_from_variable_provider->getProject($variables);
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $list_info = $this->list_info_from_variable_provider->getListInfoFromVariables($request, $variables);
        $user      = $request->getCurrentUser();

        try {
            $thread_presenter = $this->presenter_builder->getThreadPresenter(
                $list_info->getProject(),
                $user,
                $list_info->getListId(),
                $list_info->getListName(),
                (int) $variables['thread_id']
            );
        } catch (ThreadNotFoundException $exception) {
            throw new NotFoundException();
        }

        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->include_assets, 'forumml-style'));
        $layout->includeFooterJavascriptFile($this->include_assets->getFileURL('thread.js'));
        $layout->includeFooterJavascriptFile(RelativeDatesAssetsRetriever::retrieveAssetsUrl());

        $service = $list_info->getService();
        $service->displayMailingListHeaderWithAdditionalBreadcrumbs(
            $user,
            $list_info->getListName(),
            $this->breadcrumb_collection_builder->getCurrentListBreadcrumbCollectionFromRow(
                $list_info->getListRow(),
                $list_info->getProject(),
                $request,
                $list_info->getListName()
            )
        );
        $this->renderer->renderToPage(
            'one-thread',
            $thread_presenter
        );
        $service->displayFooter();
    }

    public static function getUrl(int $list_id, int $thread_id): string
    {
        return '/plugins/forumml/list/' . urlencode((string) $list_id) . '/threads/' . urlencode((string) $thread_id);
    }
}
