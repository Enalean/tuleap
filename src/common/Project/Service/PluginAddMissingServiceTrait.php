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

trait PluginAddMissingServiceTrait
{
    abstract protected function isServiceAllowedForProject(\Project $project): bool;

    /**
     * @return class-string
     */
    abstract protected function getServiceClass(): string;

    public function addMissingService(AddMissingService $event): void
    {
        if (! $this->isServiceAllowedForProject($event->project)) {
            return;
        }

        $service_class = $this->getServiceClass();
        try {
            $reflected_class = new \ReflectionClass($service_class);
            if (! $reflected_class->implementsInterface(ServiceForCreation::class)) {
                return;
            }
        } catch (\ReflectionException) {
            return;
        }

        $event->addService(
            $service_class::forServiceCreation($event->project)
        );
    }
}
