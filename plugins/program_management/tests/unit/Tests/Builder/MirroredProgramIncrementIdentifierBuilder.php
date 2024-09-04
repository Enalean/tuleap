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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementFromTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

final class MirroredProgramIncrementIdentifierBuilder
{
    public static function buildWithId(int $id): MirroredProgramIncrementIdentifier
    {
        $mirrored_id = MirroredProgramIncrementIdentifier::fromProgramIncrementAndTeam(
            RetrieveMirroredProgramIncrementFromTeamStub::withIds($id),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            ProgramIncrementIdentifierBuilder::buildWithId(48),
            TeamIdentifierBuilder::build(),
            UserIdentifierStub::buildGenericUser()
        );
        assert($mirrored_id !== null);
        return $mirrored_id;
    }
}
