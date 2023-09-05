/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
import { openModalAndReplacePlaceholders } from "@tuleap/tlp-modal";
import { buildRevocationReplaceCallback, hiddenInputReplaceCallback } from "./replacers";
import "../../themes/user-preferences.scss";

const REVOKE_BUTTONS_SELECTOR = ".oauth2-server-revoke-authorization-button";
const REVOKE_MODAL_ID = "oauth2-server-revoke-app-modal";
const REVOKE_MODAL_HIDDEN_INPUT_ID = "oauth2-server-revoke-app-modal-app-id";
const REVOKE_MODAL_DESCRIPTION = "oauth2-server-revoke-app-modal-app-name";

document.addEventListener("DOMContentLoaded", async () => {
    const language = document.body.dataset.userLocale;
    if (language === undefined) {
        throw new Error("Not able to find the user language.");
    }
    const gettext_provider = await initGettext(
        language,
        "tuleap-oauth2_server",
        (locale) =>
            import(
                /* webpackChunkName: "oauth2-server-po-" */ "../po/" + getPOFileFromLocale(locale)
            ),
    );

    openModalAndReplacePlaceholders({
        document: document,
        buttons_selector: REVOKE_BUTTONS_SELECTOR,
        modal_element_id: REVOKE_MODAL_ID,
        hidden_input_replacement: {
            input_id: REVOKE_MODAL_HIDDEN_INPUT_ID,
            hiddenInputReplaceCallback,
        },
        paragraph_replacement: {
            paragraph_id: REVOKE_MODAL_DESCRIPTION,
            paragraphReplaceCallback: buildRevocationReplaceCallback(gettext_provider),
        },
    });
});
