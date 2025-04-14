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

namespace Tuleap\ProgramManagement\Adapter\ProjectHistory;
/**
 * @psalm-immutable
 */
enum ProgramHistoryEntry : string
{
    case UpdateTeamConfiguration = 'program_update_configuration';
    public function getLabel(): string
    {
        return match ($this) {
            self::UpdateTeamConfiguration      =>  dgettext(
                'tuleap-program_management',
                'Update the configuration of Program'
            ),
        };
    }

    public function getValue(array $parameters): string
    {
        return match ($this) {
            self::UpdateTeamConfiguration      => sprintf(
                dgettext(
                    'tuleap-program_management',
                    'Configuration updated for program #%d - previous team linked [ %s ] - new teams linked [ %s ] ',
                ),
                $parameters[0] ?? '',
                $parameters[1] ?? '',
                $parameters[2] ?? '',
            ),
        };
    }
}
