<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

use Tuleap\MediawikiStandalone\Permissions\ForgeUserGroupPermission\MediawikiAdminAllProjects;

class ServiceMediawiki extends Service
{
    public function getIconName(): string
    {
        return 'fas fa-tlp-mediawiki';
    }

    public function renderInPage($title, $template, $presenter = null)
    {
        $this->displayHeader($title);

        if ($presenter) {
            $this->getRenderer()->renderToPage($template, $presenter);
        }

        $this->displayFooter();
        exit;
    }

    private function getRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(dirname(MEDIAWIKI_BASE_DIR) . '/templates');
    }

    public function displayHeader(string $title, $breadcrumbs = [], array $toolbar = [], \Tuleap\Layout\HeaderConfiguration|array $params = []): void
    {
        $breadcrumb_builder = new \Tuleap\Mediawiki\MediawikiBreadcrumbBuilder(
            new User_ForgeUserGroupPermissionsManager(
                new User_ForgeUserGroupPermissionsDao(),
            ),
        );
        $breadcrumbs        = $breadcrumb_builder->getBreadcrumbs(
            $this->project,
            UserManager::instance()->getCurrentUser(),
        );

        $title = $title . ' - ' . dgettext('tuleap-mediawiki', 'Mediawiki');
        parent::displayHeader($title, $breadcrumbs, $toolbar);
    }

    /**
     * @return bool
     */
    public function userIsAdmin(PFUser $user)
    {
        $forge_user_manager     = new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
        $has_special_permission = $forge_user_manager->doesUserHavePermission(
            $user,
            new MediawikiAdminAllProjects()
        );

        return $has_special_permission || $user->isMember($this->project->getID(), 'A');
    }

    public function getUrl(?string $url = null): string
    {
        return sprintf('/plugins/mediawiki/wiki/%s', urlencode($this->project->getUnixName()));
    }

    public function urlCanChange(): bool
    {
        return false;
    }
}
