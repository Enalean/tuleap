<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
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

namespace Tuleap\project\Admin\Navigation;

use Project;

class NavigationPermissionsDropdownPresenterBuilder
{
    const PERMISSIONS_ENTRY_SHORTNAME = 'permissions';

    public function build(Project $project, $current_pane_shortname)
    {
        $permission_links = array(
            new NavigationDropdownItemPresenter(
                _('User Permissions'),
                '/project/admin/userperms.php?' . http_build_query(array(
                    'group_id' => $project->getID(),
                    'pane' => self::PERMISSIONS_ENTRY_SHORTNAME
                ))
            ),
            new NavigationDropdownItemPresenter(
                _('Permission Request'),
                '/project/admin/permission_request.php?' . http_build_query(array(
                    'group_id' => $project->getID(),
                    'pane' => self::PERMISSIONS_ENTRY_SHORTNAME
                ))
            )
        );

        $services_quicklinks = $this->buildCoreServicesQuickLinks($project);

        $all_permission_dropdown_items = array_merge($permission_links, $services_quicklinks);

        $presenter = new NavigationDropdownPresenter(
            _('permissions'),
            self::PERMISSIONS_ENTRY_SHORTNAME,
            $current_pane_shortname,
            $all_permission_dropdown_items
        );

        return $presenter;
    }

    private function buildCoreServicesQuickLinks(Project $project)
    {
        $core_services_quicklinks = array();

        $core_services_quicklinks[] = new NavigationDropdownTitlePresenter(_("Quick access to service admin"));
        if ($project->usesForum()) {
            $core_services_quicklinks[] = new NavigationDropdownItemPresenter(
                _('Forum'),
                '/forum/admin/?' . http_build_query(array(
                    'group_id' => $project->getID(),
                    'pane' => self::PERMISSIONS_ENTRY_SHORTNAME
                ))
            );
        }
        if ($project->usesMail()) {
            $core_services_quicklinks[] = new NavigationDropdownItemPresenter(
                _('Lists'),
                '/mail/admin/?' . http_build_query(array(
                    'group_id' => $project->getID(),
                    'pane' => self::PERMISSIONS_ENTRY_SHORTNAME
                ))
            );
        }
        if ($project->usesWiki()) {
            $core_services_quicklinks[] = new NavigationDropdownItemPresenter(
                _('Wiki'),
                '/wiki/admin/?' . http_build_query(array(
                    'group_id' => $project->getID(),
                    'pane' => self::PERMISSIONS_ENTRY_SHORTNAME
                ))
            );
        }
        if ($project->usesNews()) {
            $core_services_quicklinks[] = new NavigationDropdownItemPresenter(
                _('News'),
                '/news/admin/?' . http_build_query(array(
                    'group_id' => $project->getID(),
                    'pane' => self::PERMISSIONS_ENTRY_SHORTNAME
                ))
            );
        }
        if ($project->usesCVS()) {
            $core_services_quicklinks[] = new NavigationDropdownItemPresenter(
                _('CVS'),
                '/cvs/?' . http_build_query(array(
                    'group_id' => $project->getID(),
                    'func' => 'admin',
                    'pane' => self::PERMISSIONS_ENTRY_SHORTNAME
                ))
            );
        }
        if ($project->usesSVN()) {
            $core_services_quicklinks[] = new NavigationDropdownItemPresenter(
                _('Subversion'),
                '/svn/admin/?' . http_build_query(array(
                    'group_id' => $project->getID(),
                    'pane' => self::PERMISSIONS_ENTRY_SHORTNAME
                ))
            );
        }
        if ($project->usesFile()) {
            $core_services_quicklinks[] = new NavigationDropdownItemPresenter(
                _('FRS'),
                '/file/admin/?' . http_build_query(array(
                    'group_id' => $project->getID(),
                    'pane' => self::PERMISSIONS_ENTRY_SHORTNAME
                ))
            );
        }
        return $core_services_quicklinks;
    }
}
