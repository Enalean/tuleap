/**
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

import { select2 } from "tlp";
import { createModal } from "@tuleap/tlp-modal";
import { createDropdown } from "@tuleap/tlp-dropdown";
import jQuery from "jquery";

!(function ($) {
    function formatOptionIcon(option) {
        const icon = document.createElement("i");
        icon.classList.add("fa", "fa-" + option.id);
        return icon;
    }

    function initIconSelectors() {
        var icon_select_elements = document.querySelectorAll(".provider-icon-selector");
        [].forEach.call(icon_select_elements, function (icon_select_element) {
            select2(icon_select_element, {
                containerCssClass: "provider-icon-container",
                dropdownCssClass: "provider-icon-results",
                minimumResultsForSearch: Infinity,
                templateResult: formatOptionIcon,
                templateSelection: formatOptionIcon,
            });
        });
    }

    function formatOptionColor(option) {
        const color = document.createElement("span");
        if (option.id !== "") {
            color.classList.add(option.id);
        }
        return color;
    }

    function initColorSelectors() {
        var color_select_elements = document.querySelectorAll(".provider-color-selector");
        [].forEach.call(color_select_elements, function (color_select_element) {
            select2(color_select_element, {
                containerCssClass: "provider-color-container",
                dropdownCssClass: "provider-color-results",
                minimumResultsForSearch: Infinity,
                templateResult: formatOptionColor,
                templateSelection: formatOptionColor,
            });
        });
    }

    function syncPreviewButton() {
        $(".provider-name").keyup(function () {
            $(this)
                .parents(".tlp-modal-body")
                .find(".provider-admin-modal-provider-button-preview > button > span")
                .text($(this).val());
        });

        $(".provider-icon-selector").change(function () {
            var icon = $(this)
                .parents(".tlp-modal-body")
                .find(".provider-admin-modal-provider-button-preview > button > i");
            icon.removeClass();
            icon.addClass("tlp-button-icon fa fa-" + $(this).val());
        });

        $(".provider-color-selector").change(function () {
            var button = $(this)
                .parents(".tlp-modal-body")
                .find(".provider-admin-modal-provider-button-preview > button");
            button.removeClass();
            button.addClass(
                "tlp-button-primary tlp-button-large provider-admin-modal-provider-button",
            );

            if ($(this).val()) {
                button.addClass($(this).val());
            }
        });
    }

    function initCreationModal() {
        var modal_generic_providers_config_element = document.getElementById(
            "siteadmin-config-providers-modal-create-generic",
        );
        var modal_generic_providers_config = createModal(modal_generic_providers_config_element);

        document
            .querySelector(".add-generic-provider-button")
            .addEventListener("click", function () {
                modal_generic_providers_config.toggle();
            });

        var modal_azure_providers_config_element = document.getElementById(
            "siteadmin-config-providers-modal-create-azure",
        );

        var modal_azure_providers_config = createModal(modal_azure_providers_config_element);

        document.querySelector(".add-azure-provider-button").addEventListener("click", function () {
            modal_azure_providers_config.toggle();
        });
        createDropdown(document.getElementById("dropdown-specific-providers"), {
            dropdown_menu: document.getElementById("dropdown-specific-providers-menu"),
        });
    }

    function initUpdateModals() {
        var update_modals_update_buttons = document.querySelectorAll(
            ".provider-action-edit-button",
        );
        [].forEach.call(update_modals_update_buttons, function (edit_button) {
            var dom_provider_modal_edit = document.getElementById(
                edit_button.getAttribute("data-edit-modal-id"),
            );
            var tlp_providers_modal_edit = createModal(dom_provider_modal_edit);

            edit_button.addEventListener("click", function () {
                tlp_providers_modal_edit.toggle();
            });
        });
    }

    function initDeleteModals() {
        var provider_action_delete_buttons = document.querySelectorAll(
            ".provider-action-delete-button",
        );
        [].forEach.call(provider_action_delete_buttons, function (delete_button) {
            var dom_provider_modal_delete = document.getElementById(
                delete_button.getAttribute("data-delete-modal-id"),
            );
            var tlp_providers_modal_delete = createModal(dom_provider_modal_delete);

            delete_button.addEventListener("click", function () {
                tlp_providers_modal_delete.toggle();
            });
        });
    }

    $(document).ready(function () {
        initIconSelectors();
        initColorSelectors();
        syncPreviewButton();
        initCreationModal();
        initUpdateModals();
        initDeleteModals();
    });
})(jQuery);
