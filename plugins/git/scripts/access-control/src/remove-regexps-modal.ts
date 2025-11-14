/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { createModal } from "@tuleap/tlp-modal";
import { selectOrThrow, getAttributeOrThrow } from "@tuleap/dom";

export const initToggleWarningModal = (): void => {
    const submit_button = selectOrThrow(document, "#save-permissions-with-regexp");
    const openModal = (): void => {
        createModal(selectOrThrow(document, "#modal-regexp-delete"), {
            destroy_on_hide: true,
            keyboard: true,
            dismiss_on_backdrop_click: true,
        }).show();
    };

    submit_button.addEventListener("click", (event) => {
        const use_regexps_checkbox = document.querySelector<HTMLInputElement>(".use-regexp");
        if (!(use_regexps_checkbox instanceof HTMLInputElement)) {
            return;
        }

        const is_using_regexps = use_regexps_checkbox?.checked;
        const are_regexps_enabled = getAttributeOrThrow(submit_button, "data-are-regexp-enabled");
        const are_regexps_conflicting = getAttributeOrThrow(
            submit_button,
            "data-are-regexp-conflicting",
        );

        if (!is_using_regexps && are_regexps_enabled) {
            event.preventDefault();
            openModal();
            return;
        }

        if (is_using_regexps && are_regexps_conflicting) {
            event.preventDefault();
            use_regexps_checkbox.removeAttribute("checked");
            openModal();
        }
    });
};
