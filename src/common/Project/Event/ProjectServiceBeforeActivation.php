<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Event;

use Project;
use Tuleap\Event\Dispatchable;

class ProjectServiceBeforeActivation implements Dispatchable
{
    public const string NAME = 'project_service_before_activation';

    private Project $project;
    private string $service_short_name;
    private bool $plugin_set_a_value       = false;
    private string $warning_message        = '';
    private bool $service_can_be_activated = false;
    private \PFUser $user;

    public function __construct(Project $project, string $service_short_name, \PFUser $user)
    {
        $this->project            = $project;
        $this->service_short_name = $service_short_name;
        $this->user               = $user;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getServiceShortname(): string
    {
        return $this->service_short_name;
    }

    public function isForService(string $service): bool
    {
        return $this->service_short_name === $service;
    }

    public function doesPluginSetAValue(): bool
    {
        return $this->plugin_set_a_value;
    }

    public function canServiceBeActivated(): bool
    {
        return $this->service_can_be_activated;
    }

    public function getWarningMessage(): string
    {
        return $this->warning_message;
    }

    public function pluginSetAValue(): void
    {
        $this->plugin_set_a_value = true;
    }

    public function serviceCanBeActivated(): void
    {
        $this->service_can_be_activated = true;
    }

    public function setWarningMessage(string $message): void
    {
        $this->warning_message = $message;
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }
}
