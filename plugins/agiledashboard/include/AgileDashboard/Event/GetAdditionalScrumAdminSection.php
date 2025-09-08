<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Event;

class GetAdditionalScrumAdminSection
{
    public const string NAME = 'getAdditionalScrumAdminSection';

    /**
     * @var IScrumAdminSectionControllers[]
     */
    private $controllers = [];

    /**
     * @var \Project
     */
    private $project;

    public function __construct(\Project $project)
    {
        $this->project = $project;
    }

    public function getProject(): \Project
    {
        return $this->project;
    }

    public function addAdditionalSectionController(IScrumAdminSectionControllers $controller): void
    {
        $this->controllers[] = $controller;
    }

    public function getAdditionalSectionsControllers(): array
    {
        return $this->controllers;
    }

    public function notifyAdditionalSectionsControllers(\HTTPRequest $request): void
    {
        foreach ($this->controllers as $controller) {
            $controller->onSubmitCallback($request);
        }
    }
}
