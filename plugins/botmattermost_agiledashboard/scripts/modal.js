/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import { createModal } from "tlp";

document.addEventListener("DOMContentLoaded", () => {
    const add_modal_element = document.getElementById("admin-config-bot-add");
    const add_modal_trigger = document.getElementById("admin-config-bot-add-trigger");

    if (add_modal_element && add_modal_trigger) {
        const add_modal = createModal(add_modal_element);

        add_modal_trigger.addEventListener("click", () => {
            add_modal.show();
        });
    }

    const delete_modal_element = document.getElementById("admin-config-bot-delete");
    const delete_modal_trigger = document.getElementById("admin-config-bot-delete-trigger");

    if (delete_modal_element && delete_modal_trigger) {
        const delete_modal = createModal(delete_modal_element);

        delete_modal_trigger.addEventListener("click", () => {
            delete_modal.show();
        });
    }

    const edit_modal_element = document.getElementById("admin-config-bot-edit");
    const edit_modal_trigger = document.getElementById("admin-config-bot-edit-trigger");

    if (edit_modal_element && edit_modal_trigger) {
        const edit_modal = createModal(edit_modal_element);

        edit_modal_trigger.addEventListener("click", () => {
            edit_modal.show();
        });
    }
});
