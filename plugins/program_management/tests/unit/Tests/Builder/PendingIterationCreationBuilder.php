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

use Tuleap\ProgramManagement\Domain\Events\PendingIterationCreation;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

final class PendingIterationCreationBuilder
{
    public static function buildWithIds(int $iteration_id, int $changeset_id): PendingIterationCreation
    {
        $pending_iteration_creation = PendingIterationCreation::fromIds(
            VerifyIsIterationStub::withValidIteration(),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            VerifyIsChangesetStub::withValidChangeset(),
            $iteration_id,
            $changeset_id,
            UserIdentifierStub::buildGenericUser()
        );

        if (! $pending_iteration_creation) {
            throw new \LogicException('Pending iteration have not been created');
        }

        return $pending_iteration_creation;
    }
}
