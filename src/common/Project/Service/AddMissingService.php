<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Service;

use Service;
use Tuleap\Event\Dispatchable;

/**
 * This event is used in project administration to be able to activate a Service that is not already present in the service list (in DB).
 *
 * For historical plugins (SQL based plugin's service declaration), it's mainly for projects created after plugin
 * installation based on XML templates. When those templates don't mention a plugin (often because the plugin was created
 * after the template) the plugin would not appear in Project Admin > Services without implementing this event.
 *
 * For new plugins (created after this event) it's the new way to declare a plugin for a project.
 */
final class AddMissingService implements Dispatchable
{
    public const string NAME = 'addMissingService';

    /**
     * @readonly
     */
    public \Project $project;

    /**
     * @param Service[] $allowed_services
     */
    public function __construct(\Project $project, private array $allowed_services)
    {
        $this->project = $project;
    }

    public function addService(Service $service): void
    {
        foreach ($this->allowed_services as $already_there_service) {
            if ($already_there_service->getShortName() === $service->getShortName()) {
                return;
            }
        }
        $this->allowed_services[] = $service;
    }

    /**
     * @return Service[]
     */
    public function getAllowedServices(): array
    {
        usort($this->allowed_services, static fn (\Service $s1, \Service $s2) => $s1->getRank() <=> $s2->getRank());
        return $this->allowed_services;
    }
}
