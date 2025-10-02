/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { createDatePicker } from "@tuleap/tlp-date-picker";
import { en_US_LOCALE } from "@tuleap/core-constants";

class DatePickerWrapper extends HTMLElement {
    getDateInput(): HTMLInputElement | null {
        const date_picker = this.querySelector("#date-picker");
        if (date_picker instanceof HTMLInputElement) {
            return date_picker;
        }
        return this.querySelector("#datetime-picker");
    }

    connectedCallback(): void {
        const input = this.getDateInput();
        if (!(input instanceof HTMLInputElement)) {
            return;
        }
        createDatePicker(input, en_US_LOCALE);
    }
}

if (!window.customElements.get("tuleap-date-picker-wrapper")) {
    window.customElements.define("tuleap-date-picker-wrapper", DatePickerWrapper);
}
