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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredIterationIdentifierCollection;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

final class MirroredIterationIdentifierCollectionBuilder
{
    /**
     * @no-named-arguments
     */
    public static function withIds(int $mirrored_iteration_id, int ...$other_ids): MirroredIterationIdentifierCollection
    {
        return MirroredIterationIdentifierCollection::fromIteration(
            SearchMirroredTimeboxesStub::withIds($mirrored_iteration_id, ...$other_ids),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            IterationIdentifierBuilder::buildWithId(1234),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public static function withoutIteration(): MirroredIterationIdentifierCollection
    {
        return MirroredIterationIdentifierCollection::fromIteration(
            SearchMirroredTimeboxesStub::withNoMirrors(),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            IterationIdentifierBuilder::buildWithId(1234),
            UserIdentifierStub::buildGenericUser()
        );
    }
}
