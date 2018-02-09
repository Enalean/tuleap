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
 */

namespace Tuleap\Git\PerGroup;

class RepositoriesSectionPresenter
{
    /** @var RepositoryPermissionsPresenter[] */
    public $repository_presenters;
    /** @var bool */
    public $has_no_permission;
    /** @var string */
    public $user_group_name;
    /** @var bool */
    public $has_repositories;

    public function __construct(array $repository_presenters, $has_repositories, \ProjectUGroup $selected_ugroup = null)
    {
        $this->repository_presenters = $repository_presenters;
        $this->has_repositories      = $has_repositories;
        $this->has_no_permission     = ($has_repositories && count($repository_presenters) === 0);
        $this->user_group_name       = ($selected_ugroup)
            ? $selected_ugroup->getTranslatedName()
            : '';
    }
}
