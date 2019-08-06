<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
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

namespace Tuleap\Project\Admin\ProjectUGroup;

use CSRFSynchronizerToken;
use PFUser;
use ProjectUGroup;
use Tuleap\Project\Admin\ProjectUGroup\Details\MembersPresenter;

class ProjectUGroupPresenter
{
    public $id;
    public $project_id;
    public $name;
    public $description;
    public $binding;
    public $members;
    public $csrf_token;
    public $is_static_ugroup;
    public $locale;
    public $permissions_per_group_url;

    /**
     * @var PermissionsDelegationPresenter
     */
    public $permissions_delegation;

    public function __construct(
        ProjectUGroup $ugroup,
        PermissionsDelegationPresenter $permissions_delegation,
        BindingPresenter $binding,
        MembersPresenter $members,
        CSRFSynchronizerToken $csrf_token
    ) {
        $this->id                        = $ugroup->getId();
        $this->project_id                = $ugroup->getProjectId();
        $this->name                      = $ugroup->getTranslatedName();
        $this->description               = $ugroup->getDescription();
        $this->binding                   = $binding;
        $this->members                   = $members;
        $this->csrf_token                = $csrf_token;
        $this->is_static_ugroup          = $ugroup->isStatic();
        $this->permissions_per_group_url = $this->getPermissionPerGroupUrl();

        $this->permissions_delegation = $permissions_delegation;
    }

    private function getPermissionPerGroupUrl()
    {
        $url_params = http_build_query([
            'group_id' => $this->project_id,
            'group'    => $this->id
        ]);

        return '/project/admin/permission_per_group.php?' . $url_params;
    }
}
