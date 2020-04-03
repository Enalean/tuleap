/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { modal } from "tlp";
import Gettext from "node-gettext";
import french_translations from "../po/fr.po";

document.addEventListener("DOMContentLoaded", () => {
    const button = document.getElementById("button-ask-to-join");
    if (!button) {
        return;
    }
    const modal_element = document.getElementById(button.dataset.modalId);
    if (!modal_element) {
        return;
    }

    const join_modal = modal(modal_element);

    button.addEventListener("click", () => {
        join_modal.toggle();
    });

    const message_to_admin = document.getElementById("message-private-project");

    const gettext_provider = initGetTextProvider();
    const error_message_empty = gettext_provider.gettext(
        "Message sent to administrators should not be the default one."
    );

    message_to_admin.setCustomValidity(error_message_empty);
    message_to_admin.addEventListener("input", () => {
        checkMessageValidity(message_to_admin, error_message_empty);
    });

    function checkMessageValidity(message_to_admin, error_message_empty) {
        const message =
            message_to_admin.value === message_to_admin.placeholder ? error_message_empty : "";

        message_to_admin.setCustomValidity(message);
    }

    function initGetTextProvider() {
        const gettext_provider = new Gettext();

        const body = document.body;
        gettext_provider.addTranslations("fr_FR", "access-denied-error", french_translations);
        gettext_provider.setLocale(body.dataset.userLocale);
        gettext_provider.setTextDomain("access-denied-error");
        return gettext_provider;
    }
});
