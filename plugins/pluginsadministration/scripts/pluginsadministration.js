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

import { createModal } from "tlp";
import { filterInlineTable } from "@tuleap/filter-inline-table";

document.addEventListener("DOMContentLoaded", function () {
    var switches = document.querySelectorAll(".enable-plugin-switch");

    [].forEach.call(switches, function (switch_button) {
        const modal_id = switch_button.dataset.targetModalId;
        const form = switch_button.form;

        if (!form) {
            return;
        }

        let modal = null;
        if (modal_id) {
            const modal_element = document.getElementById(modal_id);
            if (!modal_element) {
                throw Error("Unable to find confirmation modal " + modal_id);
            }
            modal = createModal(modal_element);
            modal.addEventListener("tlp-modal-hidden", function () {
                form.reset();
            });
        }

        switch_button.addEventListener("change", function () {
            if (
                (!switch_button.checked || switch_button.dataset.withDisabledDependencies) &&
                modal
            ) {
                modal.show();
            } else {
                form.submit();
            }
        });
    });

    var install_plugin_buttons = document.querySelectorAll(".install-plugin-button");
    [].forEach.call(install_plugin_buttons, function (install_plugin_button) {
        var dom_install_plugin_modal = document.getElementById(
            install_plugin_button.dataset.modalId,
        );
        var tlp_install_plugin_modal = createModal(dom_install_plugin_modal);

        install_plugin_button.addEventListener("click", function () {
            tlp_install_plugin_modal.toggle();
        });
    });

    var uninstall_plugin_buttons = document.querySelectorAll(".uninstall-plugin-button");
    [].forEach.call(uninstall_plugin_buttons, function (uninstall_plugin_button) {
        var dom_uninstall_plugin_modal = document.getElementById(
            uninstall_plugin_button.dataset.modalId,
        );
        var tlp_uninstall_plugin_modal = createModal(dom_uninstall_plugin_modal);

        uninstall_plugin_button.addEventListener("click", function () {
            tlp_uninstall_plugin_modal.toggle();
        });
    });

    var filter = document.getElementById("filter-plugins");
    if (filter) {
        filterInlineTable(filter);
    }
});
