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

import "@tuleap/copy-to-clipboard";

export function initiateCopyImageUrl(): void {
    document
        .querySelectorAll(".pdftemplate-admin-template-image-copy")
        .forEach((element: Element) => {
            if (!(element instanceof HTMLElement)) {
                return;
            }

            const original = element.dataset.tlpTooltip;
            const icon = element.querySelector(".pdftemplate-admin-template-image-copy-icon");

            element.addEventListener("copied-to-clipboard", () => {
                element.dataset.tlpTooltip = element.dataset.tlpTooltipCopied;
                if (icon) {
                    icon.classList.remove("fa-copy");
                    icon.classList.add("fa-check");
                }
                setTimeout(() => {
                    element.dataset.tlpTooltip = original;
                    if (icon) {
                        icon.classList.remove("fa-check");
                        icon.classList.add("fa-copy");
                    }
                }, 2000);
            });
        });
}
