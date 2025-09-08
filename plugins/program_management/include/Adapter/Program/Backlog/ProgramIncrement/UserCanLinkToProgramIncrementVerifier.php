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

use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Fields\RetrieveFullArtifactLinkField;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyUserCanLinkToProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class UserCanLinkToProgramIncrementVerifier implements VerifyUserCanLinkToProgramIncrement
{
    public function __construct(
        private RetrieveUser $user_retriever,
        private RetrieveFullArtifactLinkField $field_retriever,
    ) {
    }

    #[\Override]
    public function canUserLinkToProgramIncrement(
        ProgramIncrementTrackerIdentifier $program_increment_tracker,
        UserIdentifier $user,
    ): bool {
        $pfuser = $this->user_retriever->getUserWithId($user);
        $field  = $this->field_retriever->getArtifactLinkField($program_increment_tracker);
        if (! $field) {
            return false;
        }
        return $field->userCanUpdate($pfuser);
    }
}
