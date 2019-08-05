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
    initModals();
});

function initModals() {
    const vue_modal = createVueModal();

    document.addEventListener("click", event => {
        const button = event.target;
        const is_add_button = button.id === "project-admin-services-add-button";
        const is_edit_button = button.classList.contains("project-admin-services-edit-button");
        const is_delete_button = button.classList.contains("project-admin-services-delete-button");

        if (is_add_button) {
            createAndShowModal(button);
            return;
        }

        if (is_edit_button) {
            if (typeof button.dataset.serviceJson !== "undefined") {
                vue_modal.show(button);
                return;
            }
            createAndShowModal(button);
            return;
        }

        if (is_delete_button) {
            updateDeleteModalContent(button);
            createAndShowModal(button);
        }
    });
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
