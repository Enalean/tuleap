/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { openAllTargetModalsOnClick } from "tlp";

document.addEventListener("DOMContentLoaded", function () {
    handlePromotedSwitches();
    handleDeletionButtons();
});

function handlePromotedSwitches(): void {
    for (const switch_button of document.querySelectorAll(".toggle-promoted-switch")) {
        if (!(switch_button instanceof HTMLInputElement)) {
            continue;
        }

        const form = switch_button.form;
        if (!(form instanceof HTMLFormElement)) {
            continue;
        }

        switch_button.addEventListener("change", () => {
            form.submit();
        });
    }
}

function handleDeletionButtons(): void {
    openAllTargetModalsOnClick(document, ".tracker-global-admin-delete-tracker-button");
}
