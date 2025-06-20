<?php
/**
 * Copyright Enalean (c) 2021 - Present. All rights reserved.
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

namespace Tuleap\Tracker\Semantic\Status\Done;

use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Tracker;

class SemanticDoneUsedExternalServiceEvent implements Dispatchable
{
    public const NAME = 'semanticDoneUsedExternalServiceEvent';

    private Tracker $tracker;
    /**
     * @var SemanticDoneUsedExternalService[]
     */
    private array $external_services_descriptions = [];

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function getTracker(): Tracker
    {
        return $this->tracker;
    }

    /**
     * @return SemanticDoneUsedExternalService[]
     */
    public function getExternalServicesDescriptions(): array
    {
        return $this->external_services_descriptions;
    }

    public function setExternalServicesDescriptions(SemanticDoneUsedExternalService $external_service): void
    {
        $this->external_services_descriptions[] = $external_service;
    }
}
