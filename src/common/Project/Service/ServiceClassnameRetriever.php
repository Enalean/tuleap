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

use Event;
use Service;
use ServiceFile;
use ServiceSVN;

final class ServiceClassnameRetriever
{
    /**
     * @var array<string, class-string>
     */
    private ?array $service_classnames = null;

    public function __construct(private readonly \EventManager $event_manager)
    {
    }

    /**
     * Return the name of the class to instantiate a service based on its short name
     *
     * @param string $short_name the short name of the service
     *
     * @psalm-return class-string
     */
    public function getServiceClassName(string $short_name): string
    {
        if (! $short_name) {
            return \Tuleap\Project\Service\ProjectDefinedService::class;
        }

        $this->cacheServiceClassnames();

        $classname = Service::class;
        if (isset($this->service_classnames[$short_name])) {
            $classname = $this->service_classnames[$short_name];
        }

        return $classname;
    }

    private function cacheServiceClassnames(): void
    {
        if ($this->service_classnames !== null) {
            return;
        }

        $this->service_classnames = [
            Service::FILE => ServiceFile::class,
            Service::SVN  => ServiceSVN::class,
        ];

        $this->event_manager->processEvent(
            Event::SERVICE_CLASSNAMES,
            ['classnames' => &$this->service_classnames]
        );
    }
}
