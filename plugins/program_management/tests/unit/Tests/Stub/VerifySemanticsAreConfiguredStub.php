<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\VerifySemanticsAreConfigured;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\TrackerReference;

final class VerifySemanticsAreConfiguredStub implements VerifySemanticsAreConfigured
{
    private function __construct(private bool $is_semantic_valid)
    {
    }

    public static function withValidSemantics(): self
    {
        return new self(true);
    }

    public static function withInvalidSemantics(): self
    {
        return new self(false);
    }

    #[\Override]
    public function areTrackerSemanticsWellConfigured(
        TrackerReference $tracker,
        SourceTrackerCollection $source_tracker_collection,
        ConfigurationErrorsCollector $configuration_errors,
    ): bool {
        return $this->is_semantic_valid;
    }
}
