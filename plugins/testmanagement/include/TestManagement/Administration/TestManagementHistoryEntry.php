<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Administration;

/**
 * @psalm-immutable
 */
enum TestManagementHistoryEntry : string
{
    case UpdateConfiguration = 'ttm_update_configuration';

    public function getLabel(): string
    {
        return match ($this) {
            self::UpdateConfiguration      =>  dgettext(
                'tuleap-testmanagement',
                'Update the configuration of Test Management'
            ),
        };
    }

    public function getValue(array $parameters): string
    {
        return match ($this) {
            self::UpdateConfiguration      => sprintf(
                dgettext(
                    'tuleap-testmanagement',
                    'Configuration updated - previous configuration [ campaign tracker #%d, test definition tracker #%d, test execution tracker #%d, issue tracker #%d ] - new configuration [ campaign tracker #%d, test definition tracker #%d, test execution tracker #%d, issue tracker #%d ] ',
                ),
                $parameters[0] ?? '',
                $parameters[1] ?? '',
                $parameters[2] ?? '',
                $parameters[3] ?? '',
                $parameters[4] ?? '',
                $parameters[5] ?? '',
                $parameters[6] ?? '',
                $parameters[7] ?? '',
            ),
        };
    }
}
