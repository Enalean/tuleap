/*
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

import "../themes/kanban-homepage.scss";

import { openAllTargetModalsOnClick } from "@tuleap/tlp-modal";

document.addEventListener("DOMContentLoaded", () => {
    openAllTargetModalsOnClick(document, ".kanban-create-kanban-button");

    const key = "tuleap_feedback";
    if (sessionStorage[key]) {
        const feedback = document.getElementById("feedback");
        if (feedback) {
            const success = document.createElement("div");
            success.classList.add("tlp-alert-success");
            success.textContent = sessionStorage[key];
            feedback.appendChild(success);
        }
        delete sessionStorage[key];
    }
});
