/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { getPOFileFromLocale, initGettext } from "@tuleap/gettext";
import { initFeedbacks } from "../../invite-buddies/feedback-display";
import { initNotificationsOnFormSubmit } from "../../invite-buddies/send-notifications";
import { createModal, EVENT_TLP_MODAL_HIDDEN, EVENT_TLP_MODAL_SHOWN } from "@tuleap/tlp-modal";

export async function init(): Promise<void> {
    const modal_element = document.getElementById("invite-buddies-modal");
    if (!modal_element) {
        return;
    }

    const buttons = document.querySelectorAll(".invite-buddies-button");
    if (buttons.length <= 0) {
        return;
    }

    const language = document.body.dataset.userLocale;
    if (language === undefined) {
        throw new Error("Not able to find the user language.");
    }
    const gettext_provider = await initGettext(
        language,
        "invite-buddies",
        (locale) =>
            import(
                /* webpackChunkName: "invitation-po-" */ "../../invite-buddies/po/" +
                    getPOFileFromLocale(locale)
            ),
    );

    const modal = createModal(modal_element);
    for (const button of buttons) {
        if (!(button instanceof HTMLButtonElement)) {
            continue;
        }

        button.addEventListener("click", () => {
            if (!button.disabled) {
                modal.show();
            }
        });
    }

    if (/\/project\/\d+\/admin\/members/.test(location.href)) {
        modal.addEventListener(EVENT_TLP_MODAL_HIDDEN, () => {
            if (modal_element.querySelector(".invite-buddies-email-sent")) {
                location.reload();
            }
        });
    }
    modal.addEventListener(EVENT_TLP_MODAL_HIDDEN, initFeedbacks);
    modal.addEventListener(EVENT_TLP_MODAL_SHOWN, initFeedbacks);

    initNotificationsOnFormSubmit(gettext_provider);
}
