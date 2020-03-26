/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
import { autocomplete_users_for_select2 } from "../../../src/scripts/tuleap/autocomplete-for-select2.js";

document.addEventListener("DOMContentLoaded", () => {
    const user_to_grant_element = document.getElementById("permission-delegation-add-user");

    if (user_to_grant_element) {
        autocomplete_users_for_select2(user_to_grant_element, { internal_users_only: 1 });
    }

    const modal_add_permission_element = document.getElementById("siteadmin-add-permission-modal");
    const modal_add_permission = modal(modal_add_permission_element, {});

    document.getElementById("button-grant-permission").addEventListener("click", () => {
        modal_add_permission.toggle();
    });

    const modal_revoke_permission_element = document.getElementById(
        "siteadmin-revoke-permission-modal"
    );
    const modal_revoke_permission = modal(modal_revoke_permission_element, {});

    document.getElementById("button-revoke-permission").addEventListener("click", () => {
        modal_revoke_permission.toggle();
    });

    handlePrimaryButtonState(
        'input[type="checkbox"][name="users_to_revoke[]"]',
        "#button-revoke-permission"
    );

    function handlePrimaryButtonState(source_selector, target_button_selector) {
        const source_elements = document.querySelectorAll(source_selector),
            target_button = document.querySelector(target_button_selector);

        for (const source of source_elements) {
            source.addEventListener("change", () => {
                target_button.disabled =
                    document.querySelectorAll(source_selector + ":checked").length === 0;
            });
        }
    }
});
