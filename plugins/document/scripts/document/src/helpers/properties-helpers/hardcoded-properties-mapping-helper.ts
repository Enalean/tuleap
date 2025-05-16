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

import type { Property } from "../../type";

export function getStatusProperty(all_properties: Array<Property>): Property | undefined {
    return all_properties.find((property) => property.short_name === "status");
}

interface StatusMappingById {
    [key: number]: string;
}

export function getStatusFromMapping(value: number): string {
    const status_mapping: StatusMappingById = {
        100: "none",
        101: "draft",
        102: "approved",
        103: "rejected",
    };

    const status_string = status_mapping[value];
    if (status_string) {
        return status_string;
    }

    return "none";
}

interface StatusMappingByLabel {
    [key: string]: number;
}

export function getStatusIdFromName(value: string): number {
    const status_mapping: StatusMappingByLabel = {
        none: 100,
        draft: 101,
        approved: 102,
        rejected: 103,
    };

    const status_int = status_mapping[value];
    if (status_int) {
        return status_int;
    }

    return 100;
}
