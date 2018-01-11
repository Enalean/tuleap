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

class ProjectUGroupPresenter
{
    public $id;
    public $project_id;
    public $name;
    public $description;
    public $has_permissions;
    public $permissions;
    public $binding;
    public $members;
    public $csrf_token;
    public $is_static_ugroup;
    public $locale;
    /**
     * @var PermissionsDelegationPresenter
     */
    public $permissions_delegation;

    public function __construct(
        ProjectUGroup $ugroup,
        array $permissions,
        PermissionsDelegationPresenter $permissions_delegation,
        $binding,
        $members,
        CSRFSynchronizerToken $csrf_token,
        PFUser $user
    ) {
        $this->id               = $ugroup->getId();
        $this->project_id       = $ugroup->getProjectId();
        $this->name             = $ugroup->getTranslatedName();
        $this->description      = $ugroup->getDescription();
        $this->has_permissions  = count($permissions) > 0;
        $this->permissions      = $permissions;
        $this->binding          = $binding;
        $this->members          = $members;
        $this->csrf_token       = $csrf_token;
        $this->is_static_ugroup = $ugroup->isStatic();
        $this->locale           = $user->getLocale();

        $this->permissions_delegation = $permissions_delegation;
    }
}
