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

final class ProjectVisibilityNeedsRestrictedUsersException extends \RuntimeException implements RegistrationErrorException
{
    /**
     * @psalm-var \Project::ACCESS_PRIVATE_WO_RESTRICTED|\Project::ACCESS_PUBLIC_UNRESTRICTED
     */
    private string $requested_access_level;

    /**
     * @psalm-param \Project::ACCESS_PRIVATE_WO_RESTRICTED|\Project::ACCESS_PUBLIC_UNRESTRICTED $requested_access_level
     */
    public function __construct(string $requested_access_level)
    {
        parent::__construct(
            sprintf(
                'The project visibility "%s" can only be used when the instance have enabled the restricted users',
                $requested_access_level
            )
        );
        $this->requested_access_level = $requested_access_level;
    }

    #[\Override]
    public function getI18NMessage(): string
    {
        return sprintf(
            _('The project visibility "%s" can only be used when the instance have enabled the restricted users'),
            $this->requested_access_level
        );
    }
}
