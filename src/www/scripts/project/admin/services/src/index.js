/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

import { modal as createModal } from "tlp";
import Vue from "vue";
import GettextPlugin from "vue-gettext";
import french_translations from "../po/fr.po";

import { createVueModal } from "./edit-modal-initializer.js";
import VueTranslater from "./VueTranslater.vue";

const VueTranslaterComponent = new VueTranslater();

document.addEventListener("DOMContentLoaded", () => {
    initVueGettext();
    setupAddButton();
    setupEditButtons();
    setupDeleteButtons();
});

function setupAddButton() {
    const add_button_id = "project-admin-services-add-button";
    const add_button = document.getElementById(add_button_id);
    if (!add_button) {
        throw new Error(`Could not find button #${add_button_id}`);
    }
    add_button.addEventListener("click", () => {
        createAndShowModal(add_button);
    });
}

function setupEditButtons() {
    const vue_modal = createVueModal();

    const edit_buttons = document.querySelectorAll(".project-admin-services-edit-button");
    for (const edit_button of edit_buttons) {
        const should_show_dynamic_modal = typeof edit_button.dataset.serviceJson !== "undefined";
        edit_button.addEventListener("click", () => {
            if (should_show_dynamic_modal === true) {
                vue_modal.show(edit_button);
                return;
            }
            createAndShowModal(edit_button);
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

function initVueGettext() {
    Vue.use(GettextPlugin, {
        translations: {
            fr: french_translations.messages
        },
        silent: true
    });
    Vue.config.language = document.body.dataset.userLocale;
}

function createAndShowModal(button) {
    const modal = createModal(document.getElementById(button.dataset.targetModalId), {
        destroy_on_hide: true
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
        "project-admin-services-delete-modal-description"
    );

    modal_description.innerText = "";
    modal_description.insertAdjacentHTML(
        "afterbegin",
        VueTranslaterComponent.getDeleteMessage(button)
    );
}
