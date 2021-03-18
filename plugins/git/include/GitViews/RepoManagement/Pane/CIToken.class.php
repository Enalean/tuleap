<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Git\GitViews\RepoManagement\Pane;

use Tuleap\Git\AccessRightsPresenterOptionsBuilder;
use Tuleap\Git\CIToken\BuildStatusChangePermissionDAO;
use Tuleap\Git\CIToken\BuildStatusChangePermissionManager;
use Tuleap\Git\CIToken\CITokenPanePresenterBuilder;
use Tuleap\Git\CIToken\Manager as CITokenManager;
use Tuleap\Git\CIToken\Dao as CITokenDao;
use TemplateRendererFactory;

class GitViewsRepoManagementPaneCIToken extends Pane
{
    public const ID = 'citoken';

    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    public function getIdentifier()
    {
        return self::ID;
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    public function getTitle()
    {
        return dgettext('tuleap-git', 'Token for Continuous Integration');
    }

    public function getLabel()
    {
        return dgettext('tuleap-git', 'CI Token');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    public function getContent()
    {
        $renderer  = TemplateRendererFactory::build()->getRenderer(GIT_TEMPLATE_DIR);
        $presenter = (new CITokenPanePresenterBuilder(
            new CITokenManager(new CITokenDao()),
            $this->repository,
            new AccessRightsPresenterOptionsBuilder(
                new \User_ForgeUserGroupFactory(
                    new \UserGroupDao()
                ),
                \PermissionsManager::instance()
            ),
            new BuildStatusChangePermissionManager(
                new BuildStatusChangePermissionDAO()
            )
        ))->build();

        return $renderer->renderToString('ci-token-pane', $presenter);
    }
}
