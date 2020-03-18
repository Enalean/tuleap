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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Artifact\Event;

use Tracker_Artifact_XMLImport_XMLImportFieldStrategy;
use Tuleap\Event\Dispatchable;

class ExternalStrategiesGetter implements Dispatchable
{
    public const NAME = 'getExternalStrategies';
    /**
     * @var array<string, Tracker_Artifact_XMLImport_XMLImportFieldStrategy>
     */
    private $strategies = [];

    public function addStrategies(string $name, Tracker_Artifact_XMLImport_XMLImportFieldStrategy $strategy): void
    {
        $this->strategies[$name] = $strategy;
    }

    public function getStrategies(): array
    {
        return $this->strategies;
    }
}
