<?php
/**
 * Copyright Enalean (c) 2017 - 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\Admin\Navigation;

use EventManager;
use Project;

class NavigationPermissionsDropdownPresenterBuilder
{
    public const PERMISSIONS_ENTRY_SHORTNAME = 'permissions';

    public function build(Project $project, $current_pane_shortname)
    {
        $permission_links = [];

        $permission_links[] = new NavigationDropdownItemPresenter(
            _('Per group'),
            '/project/admin/permission_per_group.php?' . http_build_query(
                [
                    'group_id' => $project->getID(),
                ]
            )
        );

        if ($project->usesTracker() || $project->usesSVN()) {
            $permission_links[] = new NavigationDropdownItemPresenter(
                _('Permissions for deprecated services'),
                '/project/admin/userperms.php?' . http_build_query(
                    [
                        'group_id' => $project->getID()
                    ]
                )
            );
        }

        $permission_links[] = new NavigationDropdownItemPresenter(
            _('Permission Request'),
            '/project/admin/permission_request.php?' . http_build_query(
                [
                    'group_id' => $project->getID(),
                ]
            )
        );

        $services_quicklinks = $this->buildServicesQuickLinks($project);

        $all_permission_dropdown_items = array_merge($permission_links, $services_quicklinks);

        $presenter = new NavigationDropdownPresenter(
            _('permissions'),
            self::PERMISSIONS_ENTRY_SHORTNAME,
            $current_pane_shortname,
            $all_permission_dropdown_items
        );

        return $presenter;
    }

    private function buildServicesQuickLinks(Project $project)
    {
        $core_services_quicklinks = [];

        if ($project->usesWiki()) {
            $core_services_quicklinks[] = new NavigationDropdownItemPresenter(
                _('Wiki'),
                '/wiki/admin/index.php?' . http_build_query([
                    'group_id' => $project->getID(),
                    'view' => 'wikiPerms'
                ])
            );
        }
        if ($project->usesFile()) {
            $core_services_quicklinks[] = new NavigationDropdownItemPresenter(
                _('FRS'),
                '/file/admin/?' . http_build_query([
                    'group_id' => $project->getID(),
                    'action' => 'edit-permissions'
                ])
            );
        }

        $quick_links_collector = new NavigationDropdownQuickLinksCollector($project);

        EventManager::instance()->processEvent($quick_links_collector);

        $plugins_quick_links = $quick_links_collector->getQuickLinksList();

        $quick_links = array_merge($core_services_quicklinks, $plugins_quick_links);

        return $this->indexQuickLinks($quick_links);
    }

    private function indexQuickLinks(array $quick_links)
    {
        if (count($quick_links) === 0) {
            return [];
        }

        usort($quick_links, function ($previous_link, $current_link) {
            return strnatcasecmp($previous_link->label, $current_link->label);
        });

        array_unshift(
            $quick_links,
            new NavigationDropdownTitlePresenter(
                _("Service permissions")
            )
        );

        return $quick_links;
    }
}
