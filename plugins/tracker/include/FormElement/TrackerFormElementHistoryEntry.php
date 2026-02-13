<?php
/**
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use LogicException;

enum TrackerFormElementHistoryEntry : string
{
    case Update = 'tracker_formelement_update';
    case Unused = 'tracker_formelement_unused';

    public static function build(string $history_type): self
    {
        $type = self::tryFrom($history_type);
        if ($type === null) {
            throw new LogicException('Unexpected form element history type match value');
        }
        return $type;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Unused =>  dgettext('tuleap-tracker', 'Unused field'),
            self::Update =>  dgettext(
                'tuleap-tracker',
                'Update field'
            ),
        };
    }

    public function getValue(TrackerFormElement $field): string
    {
        return '#' . $field->getId() . ' ' . $field->getLabel() . ' (' . $field->getTracker()->getName() . ')';
    }
}
