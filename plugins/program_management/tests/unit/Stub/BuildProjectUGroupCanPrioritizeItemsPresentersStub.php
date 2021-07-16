<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Stub;

use Tuleap\ProgramManagement\Domain\Program\Admin\CanPrioritizeItems\BuildProjectUGroupCanPrioritizeItemsPresenters;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramSelectOptionConfigurationPresenter;

final class BuildProjectUGroupCanPrioritizeItemsPresentersStub implements BuildProjectUGroupCanPrioritizeItemsPresenters
{
    /**
     * @var int|string[]
     */
    private array $ids;

    /**
     * @param int|string[] $ids
     */
    private function __construct(array $ids)
    {
        $this->ids = $ids;
    }

    /**
     * @return ProgramSelectOptionConfigurationPresenter[]
     */
    public function buildProjectUgroupCanPrioritizeItemsPresenters(ProgramForAdministrationIdentifier $program): array
    {
        $presenters = [];
        foreach ($this->ids as $id) {
            $presenters[] = new ProgramSelectOptionConfigurationPresenter($id, 'ugroups', false);
        }
        return $presenters;
    }

    /**
     * @param int|string ...$ids
     */
    public static function buildWithIds(...$ids): self
    {
        return new self($ids);
    }
}
