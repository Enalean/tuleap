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
import $ from "jquery";
import { initFeedbacks } from "../invite-buddies/feedback-display";
import { initNotificationsOnFormSubmit } from "../invite-buddies/send-notifications";

export async function init(): Promise<void> {
    const form = document.getElementById("invite-buddies-modal");
    if (!form) {
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
                /* webpackChunkName: "invitation-po-" */ "../invite-buddies/po/" +
                    getPOFileFromLocale(locale)
            ),
    );

    $("#invite-buddies-modal").on("shown", initFeedbacks).on("hidden", initFeedbacks);

    initNotificationsOnFormSubmit(gettext_provider);
}
