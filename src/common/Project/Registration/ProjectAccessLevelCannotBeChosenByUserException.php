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

namespace Tuleap\Project\Registration;

final class ProjectAccessLevelCannotBeChosenByUserException extends \RuntimeException implements RegistrationErrorException
{
    /**
     * @psalm-readonly
     */
    private string $only_acceptable_access_level;

    public function __construct(string $only_acceptable_access_level)
    {
        parent::__construct(
            sprintf(
                'The project visibility cannot be chosen by the project administrator, only "%s" can be used',
                $only_acceptable_access_level
            )
        );
        $this->only_acceptable_access_level = $only_acceptable_access_level;
    }

    #[\Override]
    public function getI18NMessage(): string
    {
        return sprintf(
            _('The project visibility cannot be chosen by the project administrator, only the access level "%s" can be used'),
            $this->only_acceptable_access_level
        );
    }
}
