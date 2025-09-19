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

namespace Tuleap\Git\Repository\View;

use GitRepository;
use Tuleap\Event\Dispatchable;

class RepositoryExternalNavigationTabsCollector implements Dispatchable
{
    public const string NAME = 'repositoryExternalNavigationTabsCollector';
    /**
     * @var array
     */
    private $tabs = [];
    private $selected_tab;
    /**
     * @var GitRepository
     */
    private $repository;

    public function __construct(GitRepository $repository, $selected_tab)
    {
        $this->selected_tab = $selected_tab;
        $this->repository   = $repository;
    }

    public function addNewTab(TabPresenter $tab_presenter)
    {
        $this->tabs[] = $tab_presenter;
    }

    public function getExternalTabs()
    {
        return $this->tabs;
    }

    /**
     * @return mixed
     */
    public function getSelectedTab()
    {
        return $this->selected_tab;
    }

    public function getRepository()
    {
        return $this->repository;
    }
}
