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

use Codendi_HTMLPurifier;

class ProjectListPresenter
{
    public $title;
    public $search_fields;
    public $results;
    public $new_project;
    public $detail_button_label;
    public $more_button_project_dashboard;
    public $more_button_project_administration;
    public $purified_pending_projects_text;
    public $are_there_pending_projects;

    public function __construct(
        $title,
        ProjectListSearchFieldsPresenter $search_fields,
        ProjectListResultsPresenter $results,
        $pending_projects_count
    ) {
        $this->title                              = $title;
        $this->search_fields                      = $search_fields;
        $this->results                            = $results;
        $this->new_project                        = $GLOBALS['Language']->getText('admin_projectlist', 'new_project');
        $this->detail_button_label                = $GLOBALS['Language']->getText('admin_projectlist', 'detail_button_label');
        $this->more_button_project_dashboard      = $GLOBALS['Language']->getText('admin_projectlist', 'more_button_project_dashboard');
        $this->more_button_project_administration = $GLOBALS['Language']->getText('admin_projectlist', 'more_button_project_administration');

        $this->are_there_pending_projects     = $pending_projects_count > 0;
        $this->purified_pending_projects_text = Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText(
                'admin_projectlist',
                'pending_projects_text',
                [
                    '/admin/approve-pending.php',
                    $pending_projects_count
                ]
            ),
            CODENDI_PURIFIER_LIGHT
        );
    }
}
