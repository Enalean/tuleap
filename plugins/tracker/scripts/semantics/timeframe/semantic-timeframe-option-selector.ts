/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { init } from "./timeframe-options-manager";

document.addEventListener("DOMContentLoaded", () => {
    const options_container = document.getElementById("semantic-timeframe-options");
    const start_date_select_box = document.getElementById("start-date");
    const end_date_select_box = document.getElementById("end-date-field");
    const option_end_date_radio_button = document.getElementById("option-end-date");
    const option_duration_radio_button = document.getElementById("option-duration");

    if (
        !(options_container instanceof HTMLElement) ||
        !(start_date_select_box instanceof HTMLSelectElement) ||
        !(end_date_select_box instanceof HTMLSelectElement) ||
        !(option_end_date_radio_button instanceof HTMLInputElement) ||
        !(option_duration_radio_button instanceof HTMLInputElement)
    ) {
        return;
    }

    init(
        document,
        options_container,
        start_date_select_box,
        end_date_select_box,
        option_end_date_radio_button,
        option_duration_radio_button
    );
});
