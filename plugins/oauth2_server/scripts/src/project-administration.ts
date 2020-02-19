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

import { setupAddModal, setupDeleteButtons } from "./modals";
import { initGettext } from "../../../../src/www/scripts/tuleap/gettext/gettext-init";

document.addEventListener("DOMContentLoaded", async () => {
    const language = document.body.dataset.userLocale;
    if (language === undefined) {
        throw new Error("Not able to find the user language.");
    }

    const gettext_provider = await initGettext(language, "tuleap-oauth2_server", locale =>
        import(/* webpackChunkName: "project-administration-po-" */ `../po/${locale}.po`)
    );
    setupAddModal(document);
    setupDeleteButtons(document, gettext_provider);
});
