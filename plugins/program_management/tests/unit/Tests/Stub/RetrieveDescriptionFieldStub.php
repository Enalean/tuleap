<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DescriptionFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveDescriptionField;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

final class RetrieveDescriptionFieldStub implements RetrieveDescriptionField
{
    /**
     * @var DescriptionFieldReference[]
     */
    private array $descriptions;

    private function __construct(DescriptionFieldReference ...$descriptions)
    {
        $this->descriptions = $descriptions;
    }

    public static function withFields(DescriptionFieldReference ...$descriptions): self
    {
        return new self(...$descriptions);
    }

    #[\Override]
    public function getDescriptionField(TrackerIdentifier $tracker_identifier): DescriptionFieldReference
    {
        if (count($this->descriptions) > 0) {
            return array_shift($this->descriptions);
        }
        throw new \LogicException('No description field configured');
    }
}
