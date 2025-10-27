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

import { createModal } from "@tuleap/tlp-modal";

export function initiateLicenseAgreementModals(): void {
    document.querySelectorAll(".frs-license-agreement-modal-link").forEach((link: Element) => {
        link.addEventListener("click", function (event) {
            event.preventDefault();
            const file_id = link.getAttribute("data-file-id");
            if (file_id === null) {
                return;
            }
            const agreement_id = link.getAttribute("data-agreement-id");
            if (agreement_id === null) {
                return;
            }

            document
                .getElementById("frs-license-agreement-accept_" + agreement_id)
                ?.setAttribute("data-download-file-id", file_id);
            const modal_element = document.getElementById(
                "frs-license-agreement-modal_" + agreement_id,
            );
            if (modal_element === null) {
                return;
            }
            const modal = createModal(modal_element);
            modal.show();
        });
    });

    document.querySelectorAll(".frs-license-agreement-accept").forEach((accept: Element) => {
        accept.addEventListener("click", function (event) {
            event.preventDefault();

            const file_id = accept.getAttribute("data-download-file-id");
            const dismiss = accept.closest(".tlp-modal")?.querySelector("[data-dismiss=modal]");
            if (dismiss instanceof HTMLElement) {
                dismiss.click();
            }

            window.open("/file/download/" + file_id);
        });
    });
}
