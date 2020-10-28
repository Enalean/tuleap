<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All rights reserved
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

namespace Tuleap\SVN\Explorer;

use CSRFSynchronizerToken;
use Project;
use Tuleap\SVN\Repository\RuleName;

/**
 * @psalm-immutable
 */
class ExplorerPresenter
{
    /**
     * @var int
     */
    public $group_id;
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var string
     */
    public $validate_name;
    /**
     * @var bool
     */
    public $is_admin;
    /**
     * @var array
     */
    public $repository_list;
    /**
     * @var bool
     */
    public $has_repositories;

    public function __construct(
        Project $project,
        CSRFSynchronizerToken $csrf,
        array $repository_list,
        bool $is_admin
    ) {
        $this->group_id         = (int) $project->getID();
        $this->csrf_token       = $csrf;
        $this->is_admin         = $is_admin;
        $this->repository_list  = $repository_list;
        $this->has_repositories = ! empty($repository_list);
        $this->validate_name    = RuleName::PATTERN_REPOSITORY_NAME;
    }
}
