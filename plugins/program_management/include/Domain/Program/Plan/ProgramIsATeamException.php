<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

final class ProgramIsATeamException extends \Exception
{
    private string $i18n_message;

    public function __construct(int $program_id)
    {
        parent::__construct(sprintf('Project #%d can not be defined as a Team because it is already a Program', $program_id));
        $this->i18n_message = sprintf(
            dgettext(
                'tuleap-program_management',
                'Project #%d can not be defined as a Team because it is already a Program'
            ),
            $program_id
        );
    }

    public function getI18NExceptionMessage(): string
    {
        return $this->i18n_message;
    }
}
