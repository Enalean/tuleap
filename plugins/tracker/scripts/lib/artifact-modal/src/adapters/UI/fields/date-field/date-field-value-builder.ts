/*
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

import moment from "moment";
import type { EditableDateFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import type { DateFieldIdentifier, Permission } from "@tuleap/plugin-tracker-constants";

interface DateFieldValue {
    readonly field_id: number;
    readonly type: DateFieldIdentifier;
    readonly permissions: ReadonlyArray<Permission>;
    readonly value: string;
}

export function buildEditableDateFieldValue(
    field: EditableDateFieldStructure,
    date_field_value: string | null,
): DateFieldValue {
    const { field_id, type, permissions, is_time_displayed } = field;

    return {
        field_id,
        type,
        permissions,
        value: getValue(date_field_value, is_time_displayed),
    };
}

function getValue(value: string | null, is_time_displayed: boolean): string {
    if (value === null) {
        return "";
    }

    if (is_time_displayed) {
        return moment(value, moment.ISO_8601).format("YYYY-MM-DD HH:mm");
    }

    return moment(value, moment.ISO_8601).format("YYYY-MM-DD");
}
