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

namespace Tuleap\ForumML\Threads;

use HTTPRequest;
use Project;
use System_Command;
use Tuleap\date\RelativeDatesAssetsRetriever;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\ForumML\CurrentListBreadcrumbCollectionBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\MailingList\ServiceMailingList;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ThreadsController implements DispatchableWithBurningParrot, DispatchableWithRequest, DispatchableWithProject
{
    /**
     * @var ThreadsDao
     */
    private $dao;
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var \ForumMLPlugin
     */
    private $plugin;
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
     * @var ThreadsPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var System_Command
     */
    private $command;
    /**
     * @var CurrentListBreadcrumbCollectionBuilder
     */
    private $breadcrumb_collection_builder;

    public function __construct(
        \ForumMLPlugin $plugin,
        \ProjectManager $project_manager,
        ThreadsDao $dao,
        \TemplateRenderer $renderer,
        IncludeAssets $include_assets,
        ThreadsPresenterBuilder $presenter_builder,
        System_Command $command,
        CurrentListBreadcrumbCollectionBuilder $breadcrumb_collection_builder
    ) {
        $this->plugin                        = $plugin;
        $this->dao                           = $dao;
        $this->project_manager               = $project_manager;
        $this->renderer                      = $renderer;
        $this->include_assets                = $include_assets;
        $this->presenter_builder             = $presenter_builder;
        $this->command                       = $command;
        $this->breadcrumb_collection_builder = $breadcrumb_collection_builder;
    }

    public function getProject(array $variables): Project
    {
        $row = $this->dao->searchActiveList((int) $variables['id']);
        if (! $row) {
            throw new NotFoundException();
        }

        return $this->getProjectFromListRow((int) $row['group_id']);
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $list_id = (int) $variables['id'];

        $row = $this->dao->searchActiveList($list_id);
        if (! $row) {
            throw new NotFoundException();
        }

        $list_name = $row['list_name'];

        $project = $this->getProjectFromListRow((int) $row['group_id']);
        $service = $project->getService(\Service::ML);
        if (! $service instanceof ServiceMailingList) {
            throw new ForbiddenException();
        }

        if (! $this->plugin->isAllowed((int) $project->getID())) {
            throw new ForbiddenException();
        }

        $user = $request->getCurrentUser();
        if (! $this->canUserAccessToList($user, $project, $row['is_public'], $list_name)) {
            throw new ForbiddenException(
                dgettext('tuleap-forumml', 'You are not allowed to access the archives of this list')
            );
        }

        $threads_presenter = $this->presenter_builder->getThreadsPresenter(
            $project,
            $user,
            $list_id,
            $list_name,
            (int) $request->getValidated('offset', 'uint', 0),
            (string) $request->get('search'),
        );

        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->include_assets, 'forumml-style'));
        $layout->includeFooterJavascriptFile($this->include_assets->getFileURL('new-thread.js'));
        $layout->includeFooterJavascriptFile(RelativeDatesAssetsRetriever::retrieveAssetsUrl());

        $service->displayMailingListHeaderWithAdditionalBreadcrumbs(
            $user,
            $list_name,
            $this->breadcrumb_collection_builder->getCurrentListBreadcrumbCollectionFromRow($row, $project, $request, $list_name)
        );
        $this->renderer->renderToPage(
            'threads',
            $threads_presenter
        );
        $service->displayFooter();
    }

    public static function getUrl(int $list_id): string
    {
        return '/plugins/forumml/list/' . urlencode((string) $list_id) . '/threads';
    }

    public static function getSearchUrl(int $list_id, string $words): string
    {
        return self::getUrl($list_id) . '?' .
            http_build_query(
                [
                    'search' => $words,
                ]
            );
    }

    private function getProjectFromListRow(int $group_id): Project
    {
        return $this->project_manager->getProject($group_id);
    }

    private function canUserAccessToList(\PFUser $user, Project $project, int $is_public, string $list_name): bool
    {
        if ($is_public === 1) {
            return true;
        }

        if (! $user->isLoggedIn()) {
            return false;
        }

        if (! $user->isMember((int) $project->getID())) {
            return false;
        }

        $members = $this->command->exec(
            \ForgeConfig::get('mailman_bin_dir') . "/list_members " . escapeshellarg($list_name)
        );

        return in_array($user->getEmail(), $members, true);
    }
}
