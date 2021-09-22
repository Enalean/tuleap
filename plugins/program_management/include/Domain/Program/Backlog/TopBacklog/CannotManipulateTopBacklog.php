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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog;

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class CannotManipulateTopBacklog extends \RuntimeException
{
    private string $i18n_message;

    public function __construct(ProgramIdentifier $program, UserIdentifier $user_identifier)
    {
        parent::__construct(
            sprintf(
                'User #%d cannot manipulate the top backlog of the Program #%d',
                $user_identifier->getId(),
                $program->getId()
            )
        );
        $this->i18n_message = sprintf(
            dgettext(
                'tuleap-program_management',
                'User #%d cannot manipulate the top backlog of the Program #%d',
            ),
            $user_identifier->getId(),
            $program->getId()
        );
    }

    public function getI18NExceptionMessage(): string
    {
        return $this->i18n_message;
    }
}
