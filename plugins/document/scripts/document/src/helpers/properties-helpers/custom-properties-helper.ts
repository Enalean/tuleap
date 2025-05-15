/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { Property, Item } from "../../type";
import { toRaw } from "vue";

export function getCustomProperties(item: Item): Array<Property> {
    if (!item.properties) {
        return [];
    }

    const filtered_properties: Array<Property> = structuredClone(toRaw(item.properties));

    const hardcoded_properties = [
        "title",
        "description",
        "owner",
        "create_date",
        "update_date",
        "status",
        "obsolescence_date",
    ];

    const filter = filtered_properties.filter(
        ({ short_name }) => !hardcoded_properties.includes(short_name),
    );

    return filter.map((property) => {
        if (property.recursion === undefined) {
            property.recursion = "none";
        }
        return property;
    });
}
