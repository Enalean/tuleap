/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import { selectOrThrow } from "@tuleap/dom";
import { addFeedback } from "@tuleap/fp-feedback";

document.addEventListener("DOMContentLoaded", () => {
    const move_link = selectOrThrow(document, "#tracker-action-button-move");
    const vue_mount_point = selectOrThrow(document, "#move-artifact-modal");
    const move_dropdown_icon = selectOrThrow(document, "#tracker-artifact-action-icon");

    move_link.addEventListener("click", async () => {
        if (move_link.classList.contains("disabled")) {
            return;
        }

        move_dropdown_icon.classList.add("fa-spin", "fa-spinner");
        move_link.classList.add("disabled");
        try {
            const { init } = await import(/* webpackChunkName: "move-modal" */ "./modal");

            await init(vue_mount_point);
        } catch (e) {
            addFeedback("error", "Error while loading the Move Artifact modal.");
        } finally {
            move_dropdown_icon.classList.remove("fa-spin", "fa-spinner");
            move_link.classList.remove("disabled");
        }
    });
});
