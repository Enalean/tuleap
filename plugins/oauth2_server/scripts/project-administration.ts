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

import { modal as createModal } from "tlp";

document.addEventListener("DOMContentLoaded", () => {
    const button = document.getElementById("oauth2-server-add-client-button");
    const modal_element = document.getElementById("oauth2-server-add-client-modal");
    if (!button || !modal_element) {
        return;
    }

    const modal = createModal(modal_element, { keyboard: true });
    button.addEventListener("click", () => {
        modal.show();
    });
});
