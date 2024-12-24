<?php
/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Hierarchy;

enum HierarchyHistoryEntry: string
{
    case HierarchyUpdate = 'hierarchy_update';

    public function getLabel(array $parameters): string
    {
        return match ($this) {
            self::HierarchyUpdate      =>  dgettext(
                'tuleap-tracker',
                'Hierarchy updated',
            ),
            default => throw new \Exception('Unexpected match value'),
        };
    }

    public function getValue(array $parameters): string
    {
        return match ($this) {
            self::HierarchyUpdate      => sprintf(
                dgettext(
                    'tuleap-tracker',
                    'Tracker #%d new children: %s (previous children: %s)',
                ),
                $parameters[0] ?? '',
                $parameters[1] ?? dgettext('tuleap-tracker', 'empty children list'),
                $parameters[2] ?? dgettext('tuleap-tracker', 'empty previous hierarchy'),
            ),
            default => throw new \Exception('Unexpected match value'),
        };
    }
}
