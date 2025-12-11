/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import "./styles/timetracking-tab.scss";
import { openAllTargetModalsOnClick } from "@tuleap/tlp-modal";
import { createDatePicker, getLocaleWithDefault } from "@tuleap/tlp-date-picker";

document.addEventListener("DOMContentLoaded", () => {
    openAllTargetModalsOnClick(document, ".tracker-artifact-timetracking-modal-trigger");
    const locale = getLocaleWithDefault(document);
    const add_modal_date_picker_input = document.getElementById(
        "artifact-timetracking-add-date-picker",
    );
    if (add_modal_date_picker_input instanceof HTMLInputElement) {
        createDatePicker(add_modal_date_picker_input, locale);
    }
    const edit_modal_date_picker_input = document.getElementById(
        "artifact-timetracking-edit-date-picker",
    );
    if (edit_modal_date_picker_input instanceof HTMLInputElement) {
        createDatePicker(edit_modal_date_picker_input, locale);
    }
});
