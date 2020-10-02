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

import { openTargetModalIdOnClick } from "../../tuleap/modals/modal-opener";
import { EVENT_TLP_MODAL_HIDDEN, EVENT_TLP_MODAL_SHOWN } from "../../../themes/tlp/src/js/modal";
import { initFeedbacks } from "../../invite-buddies/feedback-display";
import { initNotificationsOnFormSubmit } from "../../invite-buddies/send-notifications";

export function init(): void {
    const button = document.getElementById("invite-buddies-button");
    if (!(button instanceof HTMLButtonElement)) {
        return;
    }
    if (button.disabled) {
        return;
    }

    const modal = openTargetModalIdOnClick(document, "invite-buddies-button");
    if (!modal) {
        return;
    }

    modal.addEventListener(EVENT_TLP_MODAL_HIDDEN, initFeedbacks);
    modal.addEventListener(EVENT_TLP_MODAL_SHOWN, initFeedbacks);

    initNotificationsOnFormSubmit();
}
