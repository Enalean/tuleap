<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Event;

use Project;
use Tuleap\Event\Dispatchable;

class ProjectServiceBeforeDeactivation implements Dispatchable
{
    private bool $plugin_set_a_value         = false;
    private string $warning_message          = '';
    private bool $service_can_be_deactivated = false;

    public function __construct(
        private readonly Project $project,
        private readonly string $service_short_name,
    ) {
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

    public function canServiceBeDeactivated(): bool
    {
        return $this->service_can_be_deactivated;
    }

    public function getWarningMessage(): string
    {
        return $this->warning_message;
    }

    public function pluginSetAValue(): void
    {
        $this->plugin_set_a_value = true;
    }

    public function serviceCanBeDeactivated(): void
    {
        $this->service_can_be_deactivated = true;
    }

    public function setWarningMessage(string $message): void
    {
        $this->warning_message = $message;
    }
}
