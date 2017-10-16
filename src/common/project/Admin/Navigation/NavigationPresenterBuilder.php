<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Project;

class NavigationPresenterBuilder
{
    public function build(Project $project, \HTTPRequest $request)
    {
        $project_id             = $project->getID();
        $current_pane_shortname = $request->get('pane');

        $entries = array(
            new NavigationItemPresenter(
                _('details'),
                '/project/admin/editgroupinfo.php?' . http_build_query(array('group_id' => $project_id, 'pane' => 'details')),
                'details',
                $current_pane_shortname
            ),
            new NavigationItemPresenter(
                _('members'),
                '/project/admin/?' . http_build_query(array('group_id' => $project_id, 'pane' => 'members')),
                'members',
                $current_pane_shortname
            ),
            new NavigationItemPresenter(
                _('groups'),
                '/project/admin/ugroup.php?' . http_build_query(array('group_id' => $project_id, 'pane' => 'groups')),
                'groups',
                $current_pane_shortname
            ),
            new NavigationItemPresenter(
                _('services'),
                '/project/admin/servicebar.php?' . http_build_query(array('group_id' => $project_id, 'pane' => 'services')),
                'services',
                $current_pane_shortname
            ),
            new NavigationItemPresenter(
                _('references'),
                '/project/admin/reference.php?' . http_build_query(array('group_id' => $project_id, 'pane' => 'references')),
                'references',
                $current_pane_shortname
            ),
            new NavigationDropdownPresenter(
                _('permissions'),
                'permissions',
                $current_pane_shortname,
                array(
                    new NavigationItemPresenter(
                        _('User Permissions'),
                        '/project/admin/userperms.php?' . http_build_query(array('group_id' => $project_id, 'pane' => 'permissions')),
                        '',
                        $current_pane_shortname
                    ),
                    new NavigationItemPresenter(
                        _('Permission Request'),
                        '/project/admin/permission_request.php?' . http_build_query(array('group_id' => $project_id, 'pane' => 'permissions')),
                        '',
                        $current_pane_shortname
                    )
                )
            ),
            new NavigationItemPresenter(
                _('categories'),
                '/project/admin/group_trove.php?' . http_build_query(array('group_id' => $project_id, 'pane' => 'categories')),
                'categories',
                $current_pane_shortname
            ),
            new NavigationDropdownPresenter(
                _('data'),
                'data',
                $current_pane_shortname,
                array(
                    new NavigationDropdownItemPresenter(
                        _('Project Data Export'),
                        '/project/export/index.php?' . http_build_query(array('group_id' => $project_id, 'pane' => 'data'))
                    ),
                    new NavigationDropdownItemPresenter(
                        _('Tracker Import'),
                        '/tracker/import_admin.php?' . http_build_query(array('group_id' => $project_id, 'mode' => 'admin', 'pane' => 'data'))
                    ),
                    new NavigationDropdownItemPresenter(
                        _('Project History'),
                        '/project/admin/history.php?' . http_build_query(array('group_id' => $project_id, 'pane' => 'data'))
                    ),
                    new NavigationDropdownItemPresenter(
                        _('Access Logs'),
                        '/project/stats/source_code_access.php/?' . http_build_query(array('group_id' => $project_id, 'pane' => 'data'))
                    )
                )
            ),
            new NavigationItemPresenter(
                _('labels'),
                '/project/admin/labels.php?' . http_build_query(array('group_id' => $project_id, 'pane' => 'labels')),
                'labels',
                $current_pane_shortname
            )
        );

        return new NavigationPresenter($entries);
    }
}
