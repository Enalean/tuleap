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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\PostAction;

use Tracker;
use Tuleap\Event\Dispatchable;

class GetExternalPostActionPluginsEvent implements Dispatchable
{
    public const NAME = 'getExternalPostActionPluginsEvent';

    /**
     * @var string[]
     */
    private $service_name_used = [];

    /**
     * @var Tracker
     */
    private $tracker;

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function addServiceNameUsed(string $service_name): void
    {
        $this->service_name_used[] = $service_name;
    }
    /**
     * @return string[]
     */
    public function getServiceNameUsed(): array
    {
        return $this->service_name_used;
    }

    public function getTracker(): Tracker
    {
        return $this->tracker;
    }
}
