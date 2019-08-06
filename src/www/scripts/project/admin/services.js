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
import { sanitize } from "dompurify";
import { sprintf } from "sprintf-js";
import { escaper } from "../../tuleap/escaper";
import Gettext from "node-gettext";
import french_translations from "../po/fr.po";

const gettext_provider = new Gettext();

document.addEventListener("DOMContentLoaded", () => {
    const project_admin_services = document.getElementById("project-admin-services");
    if (project_admin_services) {
        gettext_provider.addTranslations("fr_FR", "project-admin", french_translations);
        gettext_provider.setLocale(document.body.dataset.userLocale);
        gettext_provider.setTextDomain("project-admin");
    }

    initModals();
});

function initModals() {
    document.addEventListener("click", event => {
        const button = event.target;
        const is_add_button = button.id === "project-admin-services-add-button";
        const allowed_classes = [
            "project-admin-services-edit-button",
            "project-admin-services-delete-button"
        ];
        const is_button_classlist_contain_allowed_class = allowed_classes.some(classname =>
            button.classList.contains(classname)
        );

        if (is_add_button || is_button_classlist_contain_allowed_class) {
            const modal = createModal(document.getElementById(button.dataset.targetModalId), {
                destroy_on_hide: true
            });

            if (button.classList.contains("project-admin-services-delete-button")) {
                updateDeleteModalContent(button);
            }

            modal.show();
        }
    });
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
        "afterBegin",
        sanitize(
            sprintf(
                gettext_provider.gettext(
                    "You are about to delete the <b>%s</b> service. Please, confirm your action"
                ),
                escaper.html(button.dataset.serviceLabel)
            )
        )
    );
}
