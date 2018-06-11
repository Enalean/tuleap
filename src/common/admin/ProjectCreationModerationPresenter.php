<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Admin;

use CSRFSynchronizerToken;

class ProjectCreationModerationPresenter
{
    public $projects_must_be_approved = false;
    public $nb_max_projects_waiting_for_validation = -1;
    public $nb_max_projects_waiting_for_validation_per_user = -1;
    public $navbar;
    public $csrf_token;
    public $warn_local_inc;

    public function __construct(
        ProjectCreationNavBarPresenter $navbar,
        CSRFSynchronizerToken $csrf_token,
        $must_be_approved,
        $max_global,
        $max_per_user,
        $warn_local_inc
    ) {
        $this->navbar                                          = $navbar;
        $this->csrf_token                                      = $csrf_token;
        $this->projects_must_be_approved                       = $must_be_approved;
        $this->nb_max_projects_waiting_for_validation          = $max_global;
        $this->nb_max_projects_waiting_for_validation_per_user = $max_per_user;
        $this->warn_local_inc                                  = $warn_local_inc;
    }
}
