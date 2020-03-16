<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

use Tuleap\Project\ProjectAccessPresenter;

class ProjectListResultsProjectPresenter
{
    public $id;
    public $name;
    public $unix_group_name;
    public $status_label;
    public $status_class;
    public $type_label;
    public $nb_members;
    public $nb_members_title;
    public $access_presenter;
    public $member_of_title;

    public function __construct(
        $id,
        $name,
        $unix_group_name,
        $status_label,
        $status_class,
        $type_label,
        $visibility_label,
        $nb_members
    ) {
        $this->id               = $id;
        $this->name             = $name;
        $this->unix_group_name  = $unix_group_name;
        $this->status_label     = $status_label;
        $this->status_class     = $status_class;
        $this->type_label       = $type_label;
        $this->access_presenter = new ProjectAccessPresenter($visibility_label);

        $this->nb_members      = (int) $nb_members;

        $this->member_of_title = $GLOBALS['Language']->getText('admin_userlist', 'member_of', $nb_members);
    }
}
