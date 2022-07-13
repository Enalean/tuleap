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

namespace Tuleap\ProgramManagement\Domain\Team;

final class TeamIsNotAggregatedByProgramException extends \Exception
{
    private string $i18n_message;

    public function __construct(int $team_id, int $program_id)
    {
        $this->i18n_message = sprintf(
            dgettext(
                'tuleap-program_management',
                "Project %d is not a team of program %d",
            ),
            $team_id,
            $program_id
        );

        parent::__construct($this->i18n_message);
    }

    public function getI18NExceptionMessage(): string
    {
        return $this->i18n_message;
    }
}
