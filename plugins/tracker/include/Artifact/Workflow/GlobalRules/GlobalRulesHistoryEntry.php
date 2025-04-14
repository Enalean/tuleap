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

namespace Tuleap\Tracker\Artifact\Workflow\GlobalRules;

enum GlobalRulesHistoryEntry: string
{
    case AddGlobalRules    = 'add_global_rules';
    case DeleteGlobalRules = 'delete_global_rules';
    case UpdateGlobalRules = 'update_global_rules';
    public function getLabel(array $parameters): string
    {
        return match ($this) {
            self::AddGlobalRules      =>  dgettext(
                'tuleap-tracker',
                'Global rule added'
            ),
            self::DeleteGlobalRules      =>  dgettext(
                'tuleap-tracker',
                'Global rule updated'
            ),
            self::UpdateGlobalRules      =>  dgettext(
                'tuleap-tracker',
                'Global rule deleted'
            ),
            default => throw new \Exception('Unexpected match value'),
        };
    }

    public function getValue(array $parameters): string
    {
        return match ($this) {
            self::AddGlobalRules      => sprintf(
                dgettext(
                    'tuleap-tracker',
                    'Tracker #%d add rule #%d - source field #%s should be %s than target field #%s',
                ),
                $parameters[0] ?? '',
                $parameters[1] ?? '',
                $parameters[2] ?? '',
                $parameters[3] ?? '',
                $parameters[4] ?? '',
            ),
            self::UpdateGlobalRules      => sprintf(
                dgettext(
                    'tuleap-tracker',
                    'Tracker #%d updated rule #%d - previous source field #%s should be %s than target field #%s - new source field #%s should be %s than target field #%s',
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
            self::DeleteGlobalRules      => sprintf(
                dgettext(
                    'tuleap-tracker',
                    'Tracker #%d rule #%d deleted',
                ),
                $parameters[0] ?? '',
                $parameters[1] ?? ''
            ),
            default => throw new \Exception('Unexpected match value'),
        };
    }
}
