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

namespace Tuleap\Git\Events;

use Project;
use Tuleap\Event\Dispatchable;
use Tuleap\Git\GitPresenters\AdminExternalPanePresenter;

class GitAdminGetExternalPanePresenters implements Dispatchable
{
    public const NAME = 'gitAdminGetExternalPanePresenters';
    /**
     * @var Project
     */
    private $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @var AdminExternalPanePresenter[]
     */
    private $external_pane_presenters = [];

    public function getExternalPanePresenters(): array
    {
        return $this->external_pane_presenters;
    }

    public function addExternalPanePresenter(AdminExternalPanePresenter $external_pane_presenter): void
    {
        $this->external_pane_presenters[] = $external_pane_presenter;
    }

    public function getProject(): Project
    {
        return $this->project;
    }
}
