<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

class SamplePlugin
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        bindtextdomain('tuleap-svn', __DIR__ . '/../site-content');

        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS);
        $this->addHook('cssfile');
        $this->addHook(\Tuleap\admin\ProjectEdit\ProjectStatusUpdate::NAME);
        $this->addHook('SystemEvent_USER_RENAME', 'systemevent_user_rename');
        $this->addHook(SystemEvent_PROJECT_IS_PRIVATE::class, 'changeProjectRepositoriesAccess');
        $this->addHook(Event::UGROUP_RENAME);
        $this->addHook(\Tuleap\Project\Admin\Navigation\NavigationDropdownQuickLinksCollector::NAME);
    }

    public function get_system_event_class($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
    }

    public function cssFile($params)
    {
    }

    public function projectStatusUpdate(\Tuleap\admin\ProjectEdit\ProjectStatusUpdate $event): void
    {
    }

    public function systemevent_user_rename(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
    }

    /** @see SystemEvent_PROJECT_IS_PRIVATE */
    public function changeProjectRepositoriesAccess(array $params)
    {
    }

    /** @see Event::UGROUP_RENAME */
    public function ugroupRename(array $params): void
    {
    }

    public function collectProjectAdminNavigationPermissionDropdownQuickLinks(\Tuleap\Project\Admin\Navigation\NavigationDropdownQuickLinksCollector $quick_links_collector)
    {
    }
}
?>
-----
<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

class SamplePlugin
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        bindtextdomain('tuleap-svn', __DIR__ . '/../site-content');
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::GET_SYSTEM_EVENT_CLASS)]
    public function getSystemEventClass($params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
    }

    #[\Tuleap\Plugin\ListeningToEventName('cssfile')]
    public function cssfile($params): void
    {
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function projectStatusUpdate(\Tuleap\admin\ProjectEdit\ProjectStatusUpdate $event): void
    {
    }

    #[\Tuleap\Plugin\ListeningToEventName('SystemEvent_USER_RENAME')]
    public function systemEventUserRename(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
    }

    /** @see SystemEvent_PROJECT_IS_PRIVATE */
    #[\Tuleap\Plugin\ListeningToEventName(SystemEvent_PROJECT_IS_PRIVATE::class)]
    public function changeProjectRepositoriesAccess(array $params): void
    {
    }

    /** @see Event::UGROUP_RENAME */
    #[\Tuleap\Plugin\ListeningToEventName(Event::UGROUP_RENAME)]
    public function ugroupRename(array $params): void
    {
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectProjectAdminNavigationPermissionDropdownQuickLinks(\Tuleap\Project\Admin\Navigation\NavigationDropdownQuickLinksCollector $quick_links_collector): void
    {
    }
}
?>
