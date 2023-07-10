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

namespace Tuleap\Admin\ProjectCreation\ProjetFields;

use CSRFSynchronizerToken;
use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\ProjectCreationNavBarPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Project\Admin\DescriptionFields\DescriptionFieldAdminPresenterBuilder;
use Tuleap\Project\Admin\DescriptionFields\FieldsListPresenter;
use Tuleap\Project\DescriptionFieldsDao;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ProjectFieldsDisplayController implements DispatchableWithRequest
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

        $csrf_token = new CSRFSynchronizerToken('/admin/project-creation/fields');

        $layout->addJavascriptAsset(
            new JavascriptAsset(
                new IncludeAssets(__DIR__ . '/../../../../scripts/site-admin/frontend-assets', '/assets/core/site-admin'),
                "site-admin-description-fields.js"
            )
        );

        $description_fields_dao   = new DescriptionFieldsDao();
        $description_fields_infos = $description_fields_dao->searchAll();

        $field_builder    = new DescriptionFieldAdminPresenterBuilder();
        $field_presenters = $field_builder->build($description_fields_infos);

        $title = _('Project fields');

        $custom_project_fields_list_presenter = new FieldsListPresenter(
            new ProjectCreationNavBarPresenter('fields'),
            $title,
            $field_presenters,
            $csrf_token
        );

        $admin_page = new AdminPageRenderer();
        $admin_page->renderANoFramedPresenter(
            $title,
            __DIR__ . '/../../../../templates/admin/projects',
            FieldsListPresenter::TEMPLATE,
            $custom_project_fields_list_presenter
        );
    }
}
