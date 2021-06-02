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

/**
 * @psalm-immutable
 */
class SemanticDoneUsedExternalService
{
    private string $service_name;
    private string $description;

    public function __construct(string $service_name, string $description)
    {
        $this->service_name = $service_name;
        $this->description  = $description;
    }

    public function getServiceName(): string
    {
        return $this->service_name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
