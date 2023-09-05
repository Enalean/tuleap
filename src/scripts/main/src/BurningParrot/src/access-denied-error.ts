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

import { createModal } from "tlp";
import { getPOFileFromLocale, initGettext } from "@tuleap/gettext";
import type { GetText } from "@tuleap/gettext";

document.addEventListener("DOMContentLoaded", async () => {
    const button = document.getElementById("button-ask-to-join");
    if (!button) {
        return;
    }
    const modal_id = button.dataset.modalId;
    if (!modal_id) {
        return;
    }
    const modal_element = document.getElementById(modal_id);
    if (!modal_element) {
        return;
    }

    const join_modal = createModal(modal_element);

    button.addEventListener("click", () => {
        join_modal.toggle();
    });

    const message_to_admin = document.getElementById("message-private-project");

    if (!message_to_admin || !(message_to_admin instanceof HTMLTextAreaElement)) {
        return;
    }

    const gettext_provider = await initGetTextProvider();
    const error_message_empty = gettext_provider.gettext(
        "Message sent to administrators should not be the default one.",
    );

    message_to_admin.setCustomValidity(error_message_empty);
    message_to_admin.addEventListener("input", () => {
        checkMessageValidity(message_to_admin, error_message_empty);
    });

    function checkMessageValidity(
        message_to_admin: HTMLTextAreaElement,
        error_message_empty: string,
    ): void {
        const message =
            message_to_admin.value === message_to_admin.placeholder ? error_message_empty : "";

        message_to_admin.setCustomValidity(message);
    }

    function initGetTextProvider(): Promise<GetText> {
        const body = document.body;
        const locale = body.dataset.userLocale;
        if (!locale) {
            throw new Error("Not able to find the user language.");
        }

        return initGettext(
            locale,
            "access-denied-error",
            (locale) =>
                import(
                    /* webpackChunkName: "access-denied-error-po-" */ "../po/" +
                        getPOFileFromLocale(locale)
                ),
        );
    }
});
