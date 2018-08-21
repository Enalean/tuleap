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

namespace Tuleap\Git\Repository\View;

use GitRepository;

class RepositoryHeaderPresenter
{
    public $repository_path;
    public $repository_name;
    public $user_is_admin;
    public $repository_admin_url;

    /**
     * RepositoryHeaderPresenter constructor.
     * @param GitRepository $repository
     * @param string        $user_is_admin
     * @param string        $repository_admin_url
     */
    public function __construct(
        GitRepository $repository,
        $user_is_admin,
        $repository_admin_url
    ) {
        $this->repository_path      = $repository->getPathWithoutProject();
        $this->repository_name      = $repository->getLabel();
        $this->user_is_admin        = $user_is_admin;
        $this->repository_admin_url = $repository_admin_url;
    }
}
