/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { FormatReadonlyDateField } from "../../../../domain/fields/readonly-date-field/FormatReadonlyDateField";

function toBCP47(locale: string): string {
    return locale.replace("_", "-");
}

export const ReadonlyDateFieldFormatter = (locale: string): FormatReadonlyDateField => {
    const formatter = new Intl.DateTimeFormat(toBCP47(locale), {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    });

    function getDatePartsMap(date_string: string): Map<string, string> {
        const map = new Map<string, string>();

        formatter
            .formatToParts(new Date(date_string))
            .filter(({ type }) => type !== "literal")
            .forEach(({ type, value }) => {
                map.set(type, value);
            });

        return map;
    }

    return {
        format: (date_string: string): string => {
            const map = getDatePartsMap(date_string);

            return `${map.get("year")}-${map.get("month")}-${map.get("day")} ${map.get(
                "hour",
            )}:${map.get("minute")}`;
        },
    };
};
