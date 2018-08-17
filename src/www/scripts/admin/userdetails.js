/**
 * Copyright (c) Enalean SAS - 2016 - 2018. All rights reserved
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

import { modal as createModal, datePicker } from "tlp";

document.addEventListener("DOMContentLoaded", function() {
    const expiry_element = document.querySelector("#expiry");
    if (expiry_element) {
        datePicker(expiry_element);
    }

    const dom_user_change_password_modal = document.getElementById("user-change-password-modal");
    const tlp_user_change_password_modal = createModal(dom_user_change_password_modal, {});

    document
        .getElementById("siteadmin-user-details-change-password")
        .addEventListener("click", () => {
            tlp_user_change_password_modal.toggle();
        });

    const url_params = location.search;
    if (url_params.indexOf("show-change-user-password-modal") !== -1) {
        tlp_user_change_password_modal.toggle();
    }
});
