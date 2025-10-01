<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program;

final class ProgramIterationTrackerNotFoundException extends \Exception implements ProgramTrackerException
{
    private string $i18n_message;

    public function __construct(ProgramIdentifier $program_identifier)
    {
        $program_id = $program_identifier->getId();

        parent::__construct("Iteration tracker in program $program_id is not found");
        $this->i18n_message = sprintf(
            dgettext(
                'tuleap-program_management',
                'Iteration tracker in program %d is not found'
            ),
            $program_id
        );
    }

    #[\Override]
    public function getI18NExceptionMessage(): string
    {
        return $this->i18n_message;
    }
}
