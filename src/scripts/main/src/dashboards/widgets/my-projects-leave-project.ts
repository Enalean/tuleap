/*
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

import { getAttributeOrThrow } from "@tuleap/dom";

document.addEventListener("DOMContentLoaded", function (): void {
    const leave_project_forms: NodeListOf<HTMLFormElement> =
        document.querySelectorAll(".form-leave-project");

    leave_project_forms.forEach((leave_project_form) => {
        leave_project_form.addEventListener("submit", function (event): void {
            const warning_message = getAttributeOrThrow(
                leave_project_form,
                "data-confirmation-text",
            );
            // eslint-disable-next-line no-alert -- Conversion of a legacy inline handler
            if (!window.confirm(warning_message)) {
                event.preventDefault();
            }
        });
    });
});
