<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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
use Tuleap\Label\CanProjectUseLabels;
use Tuleap\Project\Service\IndexController;

class NavigationPresenterBuilder
{
    public const DATA_ENTRY_SHORTNAME = 'data';

    /**
     * @var NavigationPresenter
     */
    private $presenter;

    /**
     * @var NavigationPermissionsDropdownPresenterBuilder
     */
    private $permission_builder;

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        NavigationPermissionsDropdownPresenterBuilder $permission_builder,
        EventManager $event_manager
    ) {
        $this->permission_builder = $permission_builder;
        $this->event_manager      = $event_manager;
    }

    public function build(Project $project, HTTPRequest $request, $current_pane_shortname)
    {
        $project_id = $project->getID();
        $user       = $request->getCurrentUser();

        if ($user->isAdmin($project_id)) {
            $entries = $this->buildEntriesForAdmin($project, $current_pane_shortname);
        } else {
            $entries = $this->buildEntriesForCastratedAdmin($project, $current_pane_shortname);
        }

        $this->presenter = new NavigationPresenter($entries, $project, $current_pane_shortname);

        if ($user->isAdmin($project_id)) {
            $this->addTrackerImportEntry($project);

            $this->event_manager->processEvent($this->presenter);
        }

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
                        'mode' => 'admin'
                    )
                )
            )
        );
    }

    /**
     * @param $current_pane_shortname
     * @param $project_id
     * @return array
     */
    private function buildEntriesForAdmin(Project $project, $current_pane_shortname)
    {
        $project_id = $project->getID();

        $entries = array();

        $entries['details'] = new NavigationItemPresenter(
            _('Details'),
            '/project/admin/editgroupinfo.php?' . http_build_query(array('group_id' => $project_id)),
            'details',
            $current_pane_shortname
        );

        $entries['members'] = $this->getMembersItemPresenter($project_id, $current_pane_shortname);

        $entries['groups'] = new NavigationItemPresenter(
            _('Groups'),
            '/project/admin/ugroup.php?' . http_build_query(array('group_id' => $project_id)),
            'groups',
            $current_pane_shortname
        );

        $entries[NavigationPermissionsDropdownPresenterBuilder::PERMISSIONS_ENTRY_SHORTNAME] = $this->permission_builder->build(
            $project,
            $current_pane_shortname
        );

        $entries['services']                 = new NavigationItemPresenter(
            _('Services'),
            IndexController::getUrl($project),
            'services',
            $current_pane_shortname
        );
        if ($this->canLabelsBeUsedByProject($project)) {
            $entries['labels'] = new NavigationItemPresenter(
                _('Labels'),
                '/project/admin/labels.php?' . http_build_query(array('group_id' => $project_id)),
                'labels',
                $current_pane_shortname
            );
        }
        $entries['references']               = new NavigationItemPresenter(
            _('References'),
            '/project/admin/reference.php?' . http_build_query(array('group_id' => $project_id)),
            'references',
            $current_pane_shortname
        );
        $entries['categories']               = new NavigationItemPresenter(
            _('Categories'),
            '/project/' . (int) $project_id . '/admin/categories',
            'categories',
            $current_pane_shortname
        );
        $entries[self::DATA_ENTRY_SHORTNAME] = new NavigationDropdownPresenter(
            _('Data'),
            self::DATA_ENTRY_SHORTNAME,
            $current_pane_shortname,
            array(
                new NavigationDropdownItemPresenter(
                    _('Project Data Export'),
                    '/project/export/index.php?' . http_build_query(array('group_id' => $project_id))
                ),
                new NavigationDropdownItemPresenter(
                    _('Project History'),
                    '/project/admin/history.php?' . http_build_query(array('group_id' => $project_id)),
                    'project-history'
                ),
                new NavigationDropdownItemPresenter(
                    _('Access Logs'),
                    '/project/stats/source_code_access.php?' . http_build_query(array('group_id' => $project_id))
                )
            )
        );
        $entries['banner'] = new NavigationItemPresenter(
            _('Banner'),
            '/project/' . urlencode((string) $project_id) . '/admin/banner',
            'banner',
            $current_pane_shortname
        );

        return $entries;
    }

    private function buildEntriesForCastratedAdmin(Project $project, $current_pane_shortname)
    {
        return array(
            'members' => $this->getMembersItemPresenter($project->getID(), $current_pane_shortname)
        );
    }

    /**
     * @param $project_id
     * @param $current_pane_shortname
     * @return NavigationItemPresenter
     */
    private function getMembersItemPresenter($project_id, $current_pane_shortname)
    {
        return new NavigationItemPresenter(
            _('Members'),
            '/project/' . urlencode((string) $project_id) . '/admin/members',
            'members',
            $current_pane_shortname
        );
    }

    private function canLabelsBeUsedByProject(Project $project)
    {
        $event = new CanProjectUseLabels($project);
        $this->event_manager->processEvent($event);

        return $event->areLabelsUsable();
    }
}
