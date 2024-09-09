<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog;

use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

/**
 * I am a Tuleap Project ID (identifier) of a Program accessed from the perspective of
 * the automatic action to add to Top backlog. In this context, the current user is a
 * special Tuleap user called Workflow User (user id #0) that has permission to read everything.
 * In this context, we also do not need to check whether the Program is linked to any
 * Teams, as this has no functional impact on the Top backlog.
 * @see ProgramIdentifier for a Program that has at least one Team and that enforces permission checks
 * @psalm-immutable
 */
final readonly class ProgramIdentifierForTopBacklogAction
{
    private function __construct(public int $id)
    {
    }

    /** @return Option<self> */
    public static function fromProjectId(VerifyProgramServiceIsEnabled $verifier, int $project_id): Option
    {
        if (! ($verifier->isProgramServiceEnabled($project_id))) {
            return Option::nothing(self::class);
        }
        return Option::fromValue(new self($project_id));
    }
}
