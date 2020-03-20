<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\SVN\Admin;

use Project;
use CSRFSynchronizerToken;

class BaseGlobalAdminPresenter
{

    public $admin_groups_active;
    public $admin_groups_url;
    public $admin_groups;
    public $project_id;
    public $csrf_input;
    public $submit;
    public $title;

    public function __construct(Project $project, CSRFSynchronizerToken $token)
    {
        $this->project_id = $project->getId();

        $this->admin_groups_active = false;
        $this->admin_groups_url    = "?group_id=" . urlencode($project->getId()) . "&action=admin-groups";
        $this->admin_groups        = dgettext('tuleap-svn', 'Admin Groups');

        $this->csrf_input          = $token->fetchHTMLInput();
        $this->title               = dgettext('tuleap-svn', 'SVN Administration');
        $this->submit              = dgettext('tuleap-svn', 'Save');
    }
}
