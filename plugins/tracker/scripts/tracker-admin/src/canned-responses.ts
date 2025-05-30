/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

document.addEventListener("DOMContentLoaded", () => {
    const forms = document.querySelectorAll("form.delete-canned-response");

    forms.forEach((form: Element): void => {
        form.addEventListener("submit", (event: Event) => {
            const confirmation_message = form.getAttribute("data-confirmation-message");
            if (confirmation_message === null) {
                throw new Error(
                    "Confirmation message is missing on the delete canned response form",
                );
            }
            // eslint-disable-next-line no-alert
            if (!confirm(confirmation_message)) {
                event.preventDefault();
            }
        });
    });
});
