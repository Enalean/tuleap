/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

export function replaceSkipToMainContentLink(): void {
    const skip_to_main_content_link = document.querySelector("[data-skip-to-main-link]");
    if (!(skip_to_main_content_link instanceof HTMLAnchorElement)) {
        throw new Error("Could not find skip to main content link");
    }

    skip_to_main_content_link.addEventListener("click", (event) => {
        event.preventDefault();

        const main_content = document.querySelector("[data-test-management-main-content]");
        if (!(main_content instanceof HTMLElement)) {
            throw new Error("Could not find main content");
        }
        main_content.focus();
    });
}
