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

namespace Tuleap\layout\NewDropdown;

use PFUser;
use Project;
use Tuleap\Event\Dispatchable;

class NewDropdownProjectLinksCollector implements Dispatchable
{
    public const NAME = 'collectNewDropdownLinksForProject';

    /**
     * @var NewDropdownLinkPresenter[]
     */
    private $current_project_links = [];
    /**
     * @var PFUser
     */
    private $current_user;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var NewDropdownLinkSectionPresenter|null
     */
    private $current_context_section;

    public function __construct(
        PFUser $current_user,
        Project $project,
        ?NewDropdownLinkSectionPresenter $current_context_section
    ) {
        $this->current_user            = $current_user;
        $this->project                 = $project;
        $this->current_context_section = $current_context_section;
    }

    /**
     * @return NewDropdownLinkPresenter[]
     */
    public function getCurrentProjectLinks(): array
    {
        return $this->current_project_links;
    }

    public function addCurrentProjectLink(NewDropdownLinkPresenter $link): void
    {
        $this->current_project_links[] = $link;
    }

    public function getCurrentUser(): PFUser
    {
        return $this->current_user;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getCurrentContextSection(): ?NewDropdownLinkSectionPresenter
    {
        return $this->current_context_section;
    }
}
