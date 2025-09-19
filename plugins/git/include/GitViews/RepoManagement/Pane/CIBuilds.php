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
use Tuleap\Git\CIBuilds\BuildStatusChangePermissionDAO;
use Tuleap\Git\CIBuilds\BuildStatusChangePermissionManager;
use Tuleap\Git\CIBuilds\CIBuildsPanePresenterBuilder;
use Tuleap\Git\CIBuilds\CITokenManager;
use Tuleap\Git\CIBuilds\CITokenDao;
use TemplateRendererFactory;

class CIBuilds extends Pane
{
    public const string ID = 'cibuilds';

    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    #[\Override]
    public function getIdentifier(): string
    {
        return self::ID;
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    #[\Override]
    public function getTitle(): string
    {
        return dgettext('tuleap-git', 'CI Builds');
    }

    #[\Override]
    public function getLabel(): string
    {
        return dgettext('tuleap-git', 'CI Builds');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    #[\Override]
    public function getContent(): string
    {
        $renderer  = TemplateRendererFactory::build()->getRenderer(GIT_TEMPLATE_DIR);
        $presenter = (new CIBuildsPanePresenterBuilder(
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

        return $renderer->renderToString('ci-builds-pane', $presenter);
    }
}
