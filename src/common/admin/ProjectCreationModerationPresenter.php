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

use Tuleap\CSRFSynchronizerTokenPresenter;

/**
 * @psalm-immutable
 */
class ProjectCreationModerationPresenter
{
    public $projects_must_be_approved = false;
    public $platform_have_restricted;
    public $restricted_users_can_create_projects;
    public $nb_max_projects_waiting_for_validation = -1;
    public $nb_max_projects_waiting_for_validation_per_user = -1;
    public $navbar;
    public $csrf_token;
    public $warn_local_inc;

    public function __construct(
        ProjectCreationNavBarPresenter $navbar,
        CSRFSynchronizerTokenPresenter $csrf_token,
        bool $must_be_approved,
        int $max_global,
        int $max_per_user,
        bool $platform_have_restricted,
        bool $restricted_users_can_create_projects,
        bool $warn_local_inc
    ) {
        $this->navbar                                          = $navbar;
        $this->csrf_token                                      = $csrf_token;
        $this->projects_must_be_approved                       = $must_be_approved;
        $this->nb_max_projects_waiting_for_validation          = $max_global;
        $this->nb_max_projects_waiting_for_validation_per_user = $max_per_user;
        $this->platform_have_restricted                        = $platform_have_restricted;
        $this->restricted_users_can_create_projects            = $restricted_users_can_create_projects;
        $this->warn_local_inc                                  = $warn_local_inc;
    }
}
