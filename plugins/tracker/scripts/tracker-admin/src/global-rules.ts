/*
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

import { openAllTargetModalsOnClick } from "@tuleap/tlp-modal";

document.addEventListener("DOMContentLoaded", () => {
    openAllTargetModalsOnClick(document, ".tracker-admin-global-rules-modal-trigger");

    makeSureSourceAndTargetAreDifferentField();
});

function makeSureSourceAndTargetAreDifferentField(): void {
    const source = document.getElementById("tracker-admin-global-rules-add-source");
    const target = document.getElementById("tracker-admin-global-rules-add-target");
    const submit = document.getElementById("tracker-admin-global-rules-add-submit");
    const error = document.getElementById("tracker-admin-global-rules-add-same-error");

    if (
        !(source instanceof HTMLSelectElement) ||
        !(target instanceof HTMLSelectElement) ||
        !(submit instanceof HTMLButtonElement) ||
        !(error instanceof HTMLElement)
    ) {
        return;
    }

    const checkSelectedValueAreDifferent = (): void => {
        if (source.value === target.value) {
            submit.disabled = true;
            error.hidden = false;
            error.closest(".tlp-form-element")?.classList.add("tlp-form-element-error");
        } else {
            submit.disabled = false;
            error.hidden = true;
            error.closest(".tlp-form-element")?.classList.remove("tlp-form-element-error");
        }
    };
    source.addEventListener("change", checkSelectedValueAreDifferent);
    target.addEventListener("change", checkSelectedValueAreDifferent);
}
