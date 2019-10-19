<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\Admin;

use ProjectUGroup;
use Project;
use CSRFSynchronizerToken;

class AdminGroupsPresenter extends BaseGlobalAdminPresenter
{
    public $admin_groups_active;
    public $admin_groups_description;
    public $admin_groups_label;
    public $ugroups;

    /**
     * @param $ugroups array(array('name' => ..., 'id' => ..., 'admin' => ...), ...)
     */
    public function __construct(Project $project, CSRFSynchronizerToken $token, array $ugroups)
    {
        parent::__construct($project, $token);

        $this->admin_groups_active      = true;
        $this->admin_groups_description = dgettext('tuleap-svn', 'Select the groups which are allowed to access the SVN administration in addition to the project administrators.');
        $this->admin_groups_label       = dgettext('tuleap-svn', 'Groups:');
        $this->ugroups                  = $ugroups;
    }
}
