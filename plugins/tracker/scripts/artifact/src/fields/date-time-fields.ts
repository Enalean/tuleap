/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

import { createDatePicker, getLocaleWithDefault } from "@tuleap/tlp-date-picker";

export function initDateTimeFields(): void {
    const user_locale = getLocaleWithDefault(document);

    document.querySelectorAll(".datetime-picker").forEach((input_datetime) => {
        if (!(input_datetime instanceof HTMLInputElement)) {
            return;
        }

        createDatePicker(input_datetime, user_locale, {
            onOpen: [
                (selectedDates, dateStr, instance): void => {
                    if (instance.selectedDates.length === 0) {
                        instance.setDate(new Date(), false);
                    }
                },
            ],
        });
    });
}
