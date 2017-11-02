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

use EventManager;
use HTTPRequest;
use Project;
use Service;

class NavigationPresenterBuilder
{
    const DATA_ENTRY_SHORTNAME = 'data';

    /**
     * @var NavigationPresenter
     */
    private $presenter;
    /**
     * @var NavigationPermissionsDropdownPresenterBuilder
     */
    private $permission_builder;

    public function __construct(NavigationPermissionsDropdownPresenterBuilder $permission_builder)
    {
        $this->permission_builder = $permission_builder;
    }

    public function build(Project $project, HTTPRequest $request, $current_pane_shortname)
    {
        $project_id             = $project->getID();
        $current_pane_shortname = $current_pane_shortname ?: $request->get('pane');

        $entries = array();


        $entries['details'] = new NavigationItemPresenter(
            _('details'),
            '/project/admin/editgroupinfo.php?' . http_build_query(array('group_id' => $project_id)),
            'details',
            $current_pane_shortname
        );
        $entries['members'] = new NavigationItemPresenter(
            _('Members'),
            '/project/admin/members.php?' . http_build_query(array('group_id' => $project_id)),
            'members',
            $current_pane_shortname
        );
        $entries['groups'] = new NavigationItemPresenter(
            _('groups'),
            '/project/admin/ugroup.php?' . http_build_query(array('group_id' => $project_id)),
            'groups',
            $current_pane_shortname
        );

        $entries[NavigationPermissionsDropdownPresenterBuilder::PERMISSIONS_ENTRY_SHORTNAME] = $this->permission_builder->build($project, $current_pane_shortname);

        $entries['services'] = new NavigationItemPresenter(
            _('services'),
            '/project/admin/servicebar.php?' . http_build_query(array('group_id' => $project_id)),
            'services',
            $current_pane_shortname
        );
        $entries['labels'] = new NavigationItemPresenter(
            _('labels'),
            '/project/admin/labels.php?' . http_build_query(array('group_id' => $project_id)),
            'labels',
            $current_pane_shortname
        );
        $entries['references'] = new NavigationItemPresenter(
            _('references'),
            '/project/admin/reference.php?' . http_build_query(array('group_id' => $project_id)),
            'references',
            $current_pane_shortname
        );
        $entries['categories'] = new NavigationItemPresenter(
            _('categories'),
            '/project/admin/group_trove.php?' . http_build_query(array('group_id' => $project_id)),
            'categories',
            $current_pane_shortname
        );
        $entries[self::DATA_ENTRY_SHORTNAME] = new NavigationDropdownPresenter(
            _('data'),
            self::DATA_ENTRY_SHORTNAME,
            $current_pane_shortname,
            array(
                new NavigationDropdownItemPresenter(
                    _('Project Data Export'),
                    '/project/export/index.php?' . http_build_query(array('group_id' => $project_id))
                ),
                new NavigationDropdownItemPresenter(
                    _('Project History'),
                    '/project/admin/history.php?' . http_build_query(array('group_id' => $project_id))
                ),
                new NavigationDropdownItemPresenter(
                    _('Access Logs'),
                    '/project/stats/source_code_access.php/?' . http_build_query(array('group_id' => $project_id))
                )
            )
        );

        $this->presenter = new NavigationPresenter($entries, $project, $current_pane_shortname);

        $this->addTrackerImportEntry($project);

        EventManager::instance()->processEvent($this->presenter);

        return $this->presenter;
    }

    private function addTrackerImportEntry(Project $project)
    {
        $service = $project->getService(Service::TRACKERV3);

        if (! $service) {
            return;
        }

        $this->presenter->addDropdownItem(
            self::DATA_ENTRY_SHORTNAME,
            new NavigationDropdownItemPresenter(
                _('Trackers v3 import'),
                '/tracker/import_admin.php?' . http_build_query(
                    array(
                        'group_id' => $project->getID(),
                        'mode' => 'admin',
                        'pane' => self::DATA_ENTRY_SHORTNAME
                    )
                )
            )
        );
    }
}
