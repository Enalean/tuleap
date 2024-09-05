<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Feature\Links;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchFeaturesInChangesetStub;

final class LinkedFeaturesDiffTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsAFeaturesDiffFromChangesets(): void
    {
        $update                       = ProgramIncrementUpdateBuilder::build();
        $program_increment_changed    = ProgramIncrementChanged::fromUpdate($update);
        $search_features_in_changeset = SearchFeaturesInChangesetStub::build();

        $search_features_in_changeset->withChangesetsAndFeatures($update->getChangeset(), [100]);
        $search_features_in_changeset->withChangesetsAndFeatures($update->getOldChangeset(), [100, 101, 102]);

        $diff = LinkedFeaturesDiff::build($search_features_in_changeset, $program_increment_changed);

        self::assertEquals([101, 102], $diff->getRemovedFeaturesIds());
    }
}
