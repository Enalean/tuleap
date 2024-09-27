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
 */

import type { DateCardField } from "./type";
import { getLocaleOrThrow, getTimezoneOrThrow, IntlFormatter } from "@tuleap/date-helper";

export function getDateToDisplay(card_field: DateCardField): string {
    const formatter = IntlFormatter(
        getLocaleOrThrow(document),
        getTimezoneOrThrow(document),
        card_field.type === "lud" || card_field.type === "subon" || card_field.is_time_displayed
            ? "date-with-time"
            : "date",
    );

    return formatter.format(card_field.value);
}
