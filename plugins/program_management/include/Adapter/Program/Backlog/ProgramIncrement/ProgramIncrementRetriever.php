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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\RetrieveProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\UserCanPlanInProgramIncrementVerifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveCrossRef;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveStatusValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveTimeframeValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveTitleValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveUri;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\VerifyUserCanUpdateTimebox;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class ProgramIncrementRetriever implements RetrieveProgramIncrement
{
    public function __construct(
        private RetrieveStatusValueUserCanSee $retrieve_status,
        private RetrieveTitleValueUserCanSee $retrieve_title,
        private RetrieveTimeframeValueUserCanSee $retrieve_timeframe,
        private RetrieveUri $retrieve_uri,
        private RetrieveCrossRef $retrieve_cross_ref,
        private VerifyUserCanUpdateTimebox $verify_user_can_update,
        private UserCanPlanInProgramIncrementVerifier $user_can_plan_verifier,
    ) {
    }

    #[\Override]
    public function retrieveProgramIncrementById(
        UserIdentifier $user_identifier,
        ProgramIncrementIdentifier $increment_identifier,
    ): ?ProgramIncrement {
        return ProgramIncrement::build(
            $this->retrieve_status,
            $this->retrieve_title,
            $this->retrieve_timeframe,
            $this->retrieve_uri,
            $this->retrieve_cross_ref,
            $this->verify_user_can_update,
            $this->user_can_plan_verifier,
            $user_identifier,
            $increment_identifier,
        );
    }
}
