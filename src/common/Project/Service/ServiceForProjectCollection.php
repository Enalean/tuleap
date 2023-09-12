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

namespace Tuleap\Project\Service;

use LogicException;
use Service;

final class ServiceForProjectCollection
{
    /**
     * @var array<string, array{is_used: int|bool, ...}>
     */
    private array $service_data_array = [];

    /**
     * @var array<string, Service>
     */
    private array $services = [];
    private bool $is_cached = false;

    public function __construct(
        private readonly \Project $project,
        private readonly ListOfAllowedServicesForProjectRetriever $service_manager,
    ) {
    }

    public function getMinimalRank(): int
    {
        // get it, no matter if summary is enabled or not
        $this->cacheServices();
        return isset($this->services[Service::SUMMARY]) ? $this->services[Service::SUMMARY]->getRank() : 1;
    }

    /**
     * @return array<string, Service>
     */
    public function getServices(): array
    {
        $this->cacheServices();
        return $this->services;
    }

    public function getService(string $service_name): ?Service
    {
        $this->cacheServices();
        return $this->usesService($service_name) ? $this->services[$service_name] : null;
    }

    public function usesService(string $service_short_name): bool
    {
        $data = $this->getServicesData();
        return isset($data[$service_short_name]) && $data[$service_short_name]['is_used'];
    }

    /**
     * This method is designed to work only with @see \Tuleap\Test\Builders\ProjectTestBuilder moreover it only works
     * to be able to use @see usesService (and friends) in tests. Using this for other service releated methods **will**
     * break.
     *
     * @param array{0: string, 1: Service}|string ...$services
     */
    public function addUsedServices(...$services): void
    {
        if ($this->is_cached) {
            throw new LogicException('This method is not supposed to be called after caching of Services');
        }

        $this->is_cached = true;

        $this->service_data_array = [];
        $this->services           = []; // Gonna break tests that rely on Services but needed to stop caching in @see cacheServices
        foreach ($services as $service) {
            if (is_string($service)) {
                $this->service_data_array[$service] = ['is_used' => true];
            } else {
                $this->service_data_array[$service[0]] = ['is_used' => true];
                $this->services[$service[0]]           = $service[1];
            }
        }
    }

    private function cacheServices(): void
    {
        if ($this->is_cached) {
            return;
        }

        $this->is_cached = true;

        // Get Service data
        $allowed_services = $this->service_manager->getListOfAllowedServicesForProject($this->project);
        if (count($allowed_services) < 1) {
            $this->service_data_array = [];
        }
        $j = 1;
        foreach ($allowed_services as $service) {
            $res_row    = $service->data;
            $short_name = $service->getShortName();
            if (! $short_name) {
                $short_name = (string) $j++;
            }

            $res_row['label']       = $service->getInternationalizedName();
            $res_row['description'] = $service->getInternationalizedDescription();

            $this->service_data_array[$short_name] = $res_row;
            $this->services[$short_name]           = $service;
        }
    }

    private function getServicesData(): array
    {
        $this->cacheServices();
        return $this->service_data_array;
    }
}
