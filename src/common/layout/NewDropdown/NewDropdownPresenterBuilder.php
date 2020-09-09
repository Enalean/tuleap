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
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;

class NewDropdownPresenterBuilder
{
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;
    /**
     * @var ProjectRegistrationUserPermissionChecker
     */
    private $project_registration_user_permission_checker;

    public function __construct(
        EventDispatcherInterface $event_dispatcher,
        ProjectRegistrationUserPermissionChecker $project_registration_user_permission_checker
    ) {
        $this->event_dispatcher                             = $event_dispatcher;
        $this->project_registration_user_permission_checker = $project_registration_user_permission_checker;
    }

    public function getPresenter(PFUser $current_user, ?Project $project, ?NewDropdownLinkSectionPresenter $current_context_section): NewDropdownPresenter
    {
        $sections = [];

        if ($current_context_section) {
            $sections[] = $current_context_section;
        }

        $this->appendProjectSection($current_user, $project, $current_context_section, $sections);
        $this->appendGlobalSection($current_user, $sections);

        return new NewDropdownPresenter($sections);
    }

    private function appendProjectSection(PFUser $current_user, ?Project $project, ?NewDropdownLinkSectionPresenter $current_context_section, array &$sections): void
    {
        if (! $project) {
            return;
        }

        $collector = $this->event_dispatcher->dispatch(new NewDropdownProjectLinksCollector($current_user, $project, $current_context_section));
        $current_project_links = $collector->getCurrentProjectLinks();
        if (empty($current_project_links)) {
            return;
        }

        $sections[] = new NewDropdownLinkSectionPresenter(
            $project->getPublicName(),
            $current_project_links,
        );
    }

    private function appendGlobalSection(PFUser $current_user, array &$sections): void
    {
        if (! $this->project_registration_user_permission_checker->isUserAllowedToCreateProjects($current_user)) {
            return;
        }

        $sections[] = new NewDropdownLinkSectionPresenter(
            \ForgeConfig::get('sys_name'),
            [
                new NewDropdownLinkPresenter('/project/new', _('Start a new project'), 'fa-archive'),
            ],
        );
    }
}
