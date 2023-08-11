/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import "../themes/split-modal.scss";

document.addEventListener("DOMContentLoaded", () => {
    const element = document.getElementById("agiledashboard-split-kanban-modal");
    if (element) {
        const modal = createModal(element, {
            destroy_on_hide: true,
            keyboard: false,
            dismiss_on_backdrop_click: false,
        });
        modal.show();
        listenToUnderstoodButton(modal);
    }
});

export function listenToUnderstoodButton(modal: Modal): void {
    const button = document.getElementById("agiledashboard-split-kanban-modal-understood");
    if (!(button instanceof HTMLButtonElement)) {
        return;
    }
    const icon = document.getElementById("agiledashboard-split-kanban-modal-understood-icon");
    if (!icon) {
        return;
    }

    const toggleIcon = (): void => {
        ["fa-check", "fa-circle-notch", "fa-spin"].forEach((classe) =>
            icon.classList.toggle(classe),
        );
    };

    button.addEventListener("click", () => {
        toggleIcon();
        button.disabled = true;
        fetch(
            "/api/v1/users/" +
                encodeURIComponent(String(document.body.dataset.userId)) +
                "/preferences?key=should_display_ad_split_modal",
            { method: "DELETE", credentials: "same-origin" },
        ).then((response) => {
            if (response.ok) {
                toggleIcon();
                modal.hide();
            }
        });
    });
}
