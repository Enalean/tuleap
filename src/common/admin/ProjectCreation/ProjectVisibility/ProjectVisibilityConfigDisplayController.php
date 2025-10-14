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

namespace Tuleap\admin\ProjectCreation\ProjectVisibility;

use CSRFSynchronizerToken;
use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\ProjectCreationNavBarPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\ProjectVisibilityOptionsForPresenterGenerator;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class ProjectVisibilityConfigDisplayController implements DispatchableWithRequest
{
    public const string TAB_NAME = 'visibility';

    /**
     * @throws ForbiddenException
     * @throws \Exception
     */
    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $csrf_token = new CSRFSynchronizerToken('/admin/project-creation/visibility');

        $presenter = new ProjectVisibilityConfigPresenter(
            new ProjectCreationNavBarPresenter(self::TAB_NAME),
            new ProjectVisibilityOptionsForPresenterGenerator(),
            (new DefaultProjectVisibilityRetriever())->getDefaultProjectVisibility(),
            $csrf_token
        );

        $admin_renderer = new AdminPageRenderer();
        $admin_renderer->renderANoFramedPresenter(
            _('Projects visibility'),
            __DIR__ . '/../../../../templates/admin/projects',
            'project-visibility-configuration-pane',
            $presenter
        );
    }
}
