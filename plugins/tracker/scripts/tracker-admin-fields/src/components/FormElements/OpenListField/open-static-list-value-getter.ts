/*
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

import type { StaticOpenListItem } from "@tuleap/plugin-tracker-rest-api-types";

function isOpenStaticValue(item_value: unknown): item_value is StaticOpenListItem {
    return (
        typeof item_value === "object" &&
        item_value !== null &&
        "label" in item_value &&
        "value_color" in item_value
    );
}

export function getOpenStaticValue(item_value: unknown): StaticOpenListItem | null {
    if (!isOpenStaticValue(item_value)) {
        return null;
    }
    return item_value;
}
