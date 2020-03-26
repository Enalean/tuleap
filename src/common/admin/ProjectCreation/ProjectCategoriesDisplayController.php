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

namespace Tuleap\Admin\ProjectCreation;

use ForgeConfig;
use HTTPRequest;
use TroveCatDao;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\ProjectCreationNavBarPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Trove\TroveCatHierarchyRetriever;
use Tuleap\Trove\TroveCatListPresenter;

class ProjectCategoriesDisplayController implements DispatchableWithRequest
{

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $include_assets = new IncludeAssets(__DIR__ . '/../../../www/assets/core', '/assets/core');

        $layout->includeFooterJavascriptFile($include_assets->getFileURL('trovecat-admin.js'));

        $csrf_token = new \CSRFSynchronizerToken('/admin/project-creation/categories');
        $trove_dao = new TroveCatDao();
        $list_builder = new TroveCatHierarchyRetriever($trove_dao);

        $last_parent    = array();
        $already_seen   = array();
        $trove_cat_list = array();
        $hierarchy_ids  = array();

        $list_builder->retrieveFullHierarchy(0, $last_parent, $already_seen, $trove_cat_list, $hierarchy_ids);

        $presenter  = new TroveCatListPresenter(
            new ProjectCreationNavBarPresenter('categories'),
            $trove_cat_list,
            $csrf_token
        );

        $admin_renderer = new AdminPageRenderer();
        $admin_renderer->renderANoFramedPresenter(
            $GLOBALS['Language']->getText('admin_trove_cat_list', 'title'),
            ForgeConfig::get('codendi_dir') . '/src/templates/admin/projects',
            'trovecatlist',
            $presenter
        );
    }
}
