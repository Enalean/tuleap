<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs\Dashboard\User;

use PFUser;
use Tuleap\Dashboard\User\SearchByUserIdAndName;

final readonly class SearchByUserIdAndNameStub implements SearchByUserIdAndName
{
    /**
     * @param list<array{id: int, name: string, user_id: int}> $dashboards
     */
    private function __construct(private array $dashboards)
    {
    }

    /**
     * @param array{id: int, name: string, user_id: int} ...$dashboards
     */
    public static function withDashboards(array ...$dashboards): self
    {
        return new self(array_values($dashboards));
    }

    #[\Override]
    public function searchByUserIdAndName(PFUser $user, string $name): ?array
    {
        return array_find($this->dashboards, static fn (array $dashboard) => $dashboard['name'] === $name);
    }
}
