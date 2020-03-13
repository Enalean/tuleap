<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Project\Admin;

use Tuleap\Layout\PaginationPresenter;

class ProjectListResultsPresenter
{
    public $title;
    public $export_url;
    public $nb_matching_projects;
    public $matching_projects;
    public $export_csv;

    public $project_name_header;
    public $unix_group_name_header;
    public $status_header;
    public $type_header;
    public $members_header;
    public $visibility_header;
    public $no_matching_projects;

    public function __construct(
        array $matching_projects,
        $nb_matching_projects,
        $group_name_search,
        $project_status,
        $limit,
        $offset
    ) {
        $this->nb_matching_projects = $nb_matching_projects;
        $this->matching_projects    = $matching_projects;

        $base_url       = '/admin/grouplist.php';
        $default_params = array(
            'group_name_search' => $group_name_search,
            'status'            => $project_status
        );

        $this->pagination = new PaginationPresenter(
            $limit,
            $offset,
            count($this->matching_projects),
            $nb_matching_projects,
            $base_url,
            $default_params
        );

        $this->export_url = $base_url . '?' . http_build_query(array('export'   => 1) + $default_params);

        $this->project_name_header    = $GLOBALS['Language']->getText('admin_projectlist', 'project_name');
        $this->unix_group_name_header = $GLOBALS['Language']->getText('admin_projectlist', 'unix_group_name');
        $this->status_header          = $GLOBALS['Language']->getText('admin_projectlist', 'status');
        $this->type_header            = $GLOBALS['Language']->getText('admin_projectlist', 'type');
        $this->members_header         = $GLOBALS['Language']->getText('admin_projectlist', 'members');
        $this->visibility_header      = $GLOBALS['Language']->getText('admin_project', 'access_label');

        $this->title                  = $GLOBALS['Language']->getText('admin_projectlist', 'matching_projects');
        $this->export_csv             = $GLOBALS['Language']->getText('admin_projectlist', 'export_csv');

        $this->no_matching_projects   = $GLOBALS['Language']->getText('admin_projectlist', 'no_matching_projects');
    }
}
