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

import type { StaticListItem } from "@tuleap/plugin-tracker-rest-api-types";
import type { LazyboxItem } from "@tuleap/lazybox";

export function buildStaticValueForLazyboxFromStaticListItem(value: StaticListItem): LazyboxItem {
    return {
        value,
        is_disabled: false,
    };
}

export function buildStaticValueForLazyboxFromNewValueName(value_name: string): LazyboxItem {
    return {
        value: {
            id: 0,
            label: value_name,
            value_color: "",
            is_hidden: false,
        },
        is_disabled: false,
    };
}
