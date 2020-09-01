<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\User;

use Tuleap\Layout\SearchFormPresenter;
use Tuleap\Project\ProjectPresenter;

/**
 * @psalm-immutable
 */
class SwitchToPresenter
{
    /**
     * @var bool
     */
    public $is_trove_cat_enabled;
    /**
     * @var bool
     */
    public $are_restricted_users_allowed;
    /**
     * @var false|string
     */
    public $projects;
    /**
     * @var bool
     */
    public $is_search_available;
    /**
     * @var false|string
     */
    public $search_form;

    /**
     * @param ProjectPresenter[] $projects
     */
    public function __construct(
        array $projects,
        bool $are_restricted_users_allowed,
        bool $is_trove_cat_enabled,
        bool $is_search_available,
        SearchFormPresenter $search_form
    ) {
        $this->projects                     = json_encode($projects, JSON_THROW_ON_ERROR);
        $this->are_restricted_users_allowed = $are_restricted_users_allowed;
        $this->is_trove_cat_enabled         = $is_trove_cat_enabled;
        $this->is_search_available          = $is_search_available;
        $this->search_form                  = json_encode($search_form, JSON_THROW_ON_ERROR);
    }
}
