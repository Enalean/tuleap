/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import Vue from "vue";
import { getPOFileFromLocale, initVueGettextFromPoGettextPlugin } from "@tuleap/vue2-gettext-init";
import { sanitize } from "dompurify";
import { sprintf } from "sprintf-js";
import { escaper } from "@tuleap/html-escaper";
import { gettext_provider } from "./helpers/gettext_provider.js";

export async function setupModalButtons(addModalCallback, editModalCallback) {
    await initVueGettextFromPoGettextPlugin(Vue, (locale) =>
        import(/* webpackChunkName: "services-po-" */ `../po/${getPOFileFromLocale(locale)}`),
    );
    setupAddButton(addModalCallback);
    setupEditButtons(editModalCallback);
    setupDeleteButtons();
}

function setupAddButton(addModalCallback) {
    const vue_modal = addModalCallback();

    const add_button_id = "project-admin-services-add-button";
    const add_button = document.getElementById(add_button_id);
    if (!add_button) {
        throw new Error(`Could not find button #${add_button_id}`);
    }
    add_button.addEventListener("click", () => {
        vue_modal.show();
    });
}

function setupEditButtons(editModalCallback) {
    const vue_modal = editModalCallback();

    const edit_buttons = document.querySelectorAll(".project-admin-services-edit-button");
    for (const edit_button of edit_buttons) {
        if (typeof edit_button.dataset.serviceJson === "undefined") {
            throw new Error(`Could not find service JSON for edit service button`);
        }
        edit_button.addEventListener("click", () => {
            vue_modal.show(edit_button);
        });
    }
}

function setupDeleteButtons() {
    const delete_buttons = document.querySelectorAll(".project-admin-services-delete-button");
    for (const delete_button of delete_buttons) {
        delete_button.addEventListener("click", () => {
            updateDeleteModalContent(delete_button);
            createAndShowModal(delete_button);
        });
    }
}

function createAndShowModal(button) {
    const modal = createModal(document.getElementById(button.dataset.targetModalId), {
        destroy_on_hide: true,
    });

    modal.show();
}

function updateDeleteModalContent(button) {
    document.getElementById("project-admin-services-delete-modal-service-id").value =
        button.dataset.serviceId;
    updateDeleteModalDescription(button);
}

function updateDeleteModalDescription(button) {
    const modal_description = document.getElementById(
        "project-admin-services-delete-modal-description",
    );

    modal_description.innerText = "";
    modal_description.insertAdjacentHTML(
        "afterbegin",
        sanitize(
            sprintf(
                gettext_provider.$gettext(
                    "You are about to delete the <b>%s</b> service. Please, confirm your action",
                ) /* javascript-format */,
                escaper.html(button.dataset.serviceLabel),
            ),
        ),
    );
}
