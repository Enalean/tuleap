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

use Tuleap\Mediawiki\ForgeUserGroupPermission\MediawikiAdminAllProjects;

class ServiceMediawiki extends Service
{
    public function getIconName(): string
    {
        return 'fa-tlp-mediawiki';
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

    public function displayHeader(string $title, $breadcrumbs = [], array $toolbar = [], array $params = []): void
    {
        if ($this->userIsAdmin(UserManager::instance()->getCurrentUser())) {
            $toolbar[] = array(
                'title' => $GLOBALS['Language']->getText('global', 'Administration'),
                'url'   => MEDIAWIKI_BASE_URL . '/forge_admin.php?' . http_build_query(array(
                    'group_id'   => $this->project->getID(),
                ))
            );
        }

        $title       = $title . ' - ' . $GLOBALS['Language']->getText('plugin_mediawiki', 'service_lbl_key');
        parent::displayHeader($title, $breadcrumbs, $toolbar);
    }

    /**
     * @param HTTPRequest $request
     * @return bool
     */
    public function userIsAdmin(PFUser $user)
    {
        $forge_user_manager = new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
        $has_special_permission = $forge_user_manager->doesUserHavePermission(
            $user,
            new MediawikiAdminAllProjects()
        );

        return $has_special_permission || $user->isMember($this->project->getID(), 'A');
    }
}
