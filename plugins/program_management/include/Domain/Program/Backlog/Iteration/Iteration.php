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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveCrossRef;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveStatusValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveTimeframeValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveTitleValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveUri;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\VerifyUserCanUpdateTimebox;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class Iteration
{
    private function __construct(
        public int $id,
        public string $title,
        public ?string $status,
        public ?int $start_date,
        public ?int $end_date,
        public string $uri,
        public string $cross_ref,
        public bool $user_can_update,
    ) {
    }

    public static function build(
        RetrieveStatusValueUserCanSee $retrieve_status_value,
        RetrieveTitleValueUserCanSee $retrieve_title_value,
        RetrieveTimeframeValueUserCanSee $retrieve_timeframe_value,
        RetrieveUri $retrieve_uri,
        RetrieveCrossRef $retrieve_cross_ref,
        VerifyUserCanUpdateTimebox $user_can_update_verifier,
        UserIdentifier $user_identifier,
        IterationIdentifier $iteration_identifier,
    ): ?self {
        $title = $retrieve_title_value->getTitle($iteration_identifier, $user_identifier);
        if (! $title) {
            return null;
        }
        $status     = $retrieve_status_value->getLabel($iteration_identifier, $user_identifier);
        $start_date = $retrieve_timeframe_value->getStartDateValueTimestamp($iteration_identifier, $user_identifier);
        $end_date   = $retrieve_timeframe_value->getEndDateValueTimestamp($iteration_identifier, $user_identifier);

        return new self(
            $iteration_identifier->getId(),
            $title,
            $status,
            $start_date,
            $end_date,
            $retrieve_uri->getUri($iteration_identifier),
            $retrieve_cross_ref->getXRef($iteration_identifier),
            $user_can_update_verifier->canUserUpdate($iteration_identifier, $user_identifier)
        );
    }
}
