<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\NavigationTab;

use GitRepository;
use Tuleap\Git\Repository\View\TabPresenter;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;

class NavigationTabPresenterBuilder
{
    public const TAB_PULLREQUEST = 'tabs-pullrequest';
    /**
     * @var HTMLURLBuilder
     */
    private $url_builder;
    /**
     * @var Factory
     */
    private $factory;

    public function __construct(HTMLURLBuilder $url_builder, Factory $factory)
    {
        $this->url_builder = $url_builder;
        $this->factory     = $factory;
    }

    public function build(GitRepository $repository, $selected_tab)
    {
        $is_selected = $selected_tab === self::TAB_PULLREQUEST;

        $nb_pull_requests         = $this->factory->getPullRequestCount($repository)->getNbOpen();
        $has_broken_pull_requests = $this->factory->hasOpenBrokenPullRequests($repository);

        return new TabPresenter(
            $is_selected,
            $this->url_builder->getPullRequestDashboardUrl($repository),
            dgettext('tuleap-pullrequest', "Pull requests"),
            self::TAB_PULLREQUEST,
            true,
            $nb_pull_requests,
            $has_broken_pull_requests ? dgettext('tuleap-pullrequest', 'There are broken pull-requests in this repository.') : '',
        );
    }
}
