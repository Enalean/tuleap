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

use Tuleap\Layout\SearchFormPresenterBuilder;
use Tuleap\Project\ProjectPresentersBuilder;

class SwitchToPresenterBuilder
{
    /**
     * @var ProjectPresentersBuilder
     */
    private $project_presenters_builder;
    /**
     * @var SearchFormPresenterBuilder
     */
    private $search_form_presenter_builder;

    public function __construct(
        ProjectPresentersBuilder $project_presenters_builder,
        SearchFormPresenterBuilder $search_form_presenter_builder
    ) {
        $this->project_presenters_builder    = $project_presenters_builder;
        $this->search_form_presenter_builder = $search_form_presenter_builder;
    }

    public function build(\PFUser $user): ?SwitchToPresenter
    {
        if (! $user->isLoggedIn()) {
            return null;
        }

        return new SwitchToPresenter(
            $this->project_presenters_builder->build($user),
            (bool) \ForgeConfig::areRestrictedUsersAllowed(),
            (bool) \ForgeConfig::get('sys_use_trove'),
            $user->isAlive() && ! $user->isRestricted(),
            $this->search_form_presenter_builder->build(),
        );
    }
}
