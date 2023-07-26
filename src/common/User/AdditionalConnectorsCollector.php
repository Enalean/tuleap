<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\User;

use Tuleap\Event\Dispatchable;

final class AdditionalConnectorsCollector implements Dispatchable
{
    /**
     * @var AdditionalConnector[]
     */
    private array $additional_connectors = [];

    public function __construct(
        public readonly string $return_to,
    ) {
    }

    public function addConnector(AdditionalConnector $additional_connector): void
    {
        $this->additional_connectors[] = $additional_connector;
    }

    public function hasConnector(): bool
    {
        return ! empty($this->additional_connectors);
    }

    public function hasOneConnector(): bool
    {
        return count($this->additional_connectors) === 1;
    }

    public function connectors(): array
    {
        return $this->additional_connectors;
    }
}
