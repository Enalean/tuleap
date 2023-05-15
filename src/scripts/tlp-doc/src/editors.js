/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

import { datePicker } from "tlp";
import { createPopover } from "@tuleap/tlp-popovers";
import { createModal } from "@tuleap/tlp-modal";
import { createDropdown } from "@tuleap/tlp-dropdown";
import CodeMirror from "codemirror";
import "codemirror/mode/htmlmixed/htmlmixed";
import "codemirror/addon/scroll/simplescrollbars";
import { sanitize } from "dompurify";
import "@tuleap/tlp-relative-date";
import { filterInlineTable } from "@tuleap/filter-inline-table";
import {
    initAppendSelect2,
    initMultiSelect2,
    initPrependSelect2,
    initSingleSelect2,
} from "./select2";
import { initMultipleListPickers, initSingleListPickers } from "./list-picker.js";
import { initMultipleLazybox, initSingleLazybox } from "./lazybox.js";

(function loadCodeMirrorEditors() {
    var demo_panels = document.querySelectorAll(".demo");

    [].forEach.call(demo_panels, function (demo_panel) {
        var textarea = demo_panel.querySelector(".code > textarea"),
            example = demo_panel.querySelector(".example");

        if (!textarea || !example) {
            return;
        }

        var delay;
        var editor = CodeMirror.fromTextArea(textarea, {
            theme: "mdn-like",
            lineNumbers: true,
            matchBrackets: true,
            mode: "text/html",
            scrollbarStyle: "overlay",
        });
        editor.on("change", function () {
            clearTimeout(delay);
            delay = setTimeout(updatePreview, 300);
        });

        function updatePreview() {
            example.innerHTML = sanitize(editor.getValue(), {
                ADD_TAGS: ["tlp-relative-date"],
                ADD_ATTR: ["date", "absolute-date", "placement", "preference", "locale"],
            });
            var datepickers = example.querySelectorAll(".tlp-input-date");
            [].forEach.call(datepickers, function (datepicker) {
                datePicker(datepicker);
            });

            var filters = example.querySelectorAll(".tlp-search[data-target-table-id]");
            [].forEach.call(filters, function (filter) {
                filterInlineTable(filter);
            });

            initSingleSelect2(example);
            initMultiSelect2(example);
            initAppendSelect2(example);
            initPrependSelect2(example);
            initSingleListPickers(example);
            initMultipleListPickers(example);
            if (example.id === "example-lazybox-") {
                initSingleLazybox();
                initMultipleLazybox();
            }

            const example_links = example.querySelectorAll('a[href="#"]');
            [].forEach.call(example_links, (link) => {
                link.addEventListener(
                    "click",
                    (event) => event.preventDefault() && event.stopPropagation()
                );
            });

            var modal_buttons = example.querySelectorAll("[data-target^=modal-]");
            [].forEach.call(modal_buttons, function (button) {
                var modal = createModal(document.getElementById(button.dataset.target), {});
                button.addEventListener("click", function () {
                    modal.toggle();
                });
            });

            var popover_triggers = document.querySelectorAll(".popover-example");
            [].forEach.call(popover_triggers, function (trigger) {
                createPopover(trigger, document.getElementById(trigger.id + "-content"));
            });

            var popover_anchor_example_trigger = document.getElementById(
                "popover-anchor-example-trigger"
            );
            if (popover_anchor_example_trigger) {
                createPopover(
                    popover_anchor_example_trigger,
                    document.getElementById("popover-anchor-example-content"),
                    {
                        anchor: document.getElementById("popover-anchor-example"),
                    }
                );
            }

            var popover_example_dom_update_button = document.getElementById(
                "popover-example-dom-update-button"
            );
            if (popover_example_dom_update_button) {
                popover_example_dom_update_button.addEventListener("click", function () {
                    popover_example_dom_update_button.classList.toggle("tlp-button-large");
                });
            }

            var dropdown_trigger_options = document.getElementById("dropdown-example-options");
            if (dropdown_trigger_options) {
                createDropdown(dropdown_trigger_options, {
                    keyboard: false,
                    dropdown_menu: document.getElementById("dropdown-menu-example-options"),
                });
            }

            var dropdown_trigger_options_submenu_1 = document.getElementById(
                "dropdown-menu-example-options-submenu-1"
            );
            if (dropdown_trigger_options_submenu_1) {
                createDropdown(dropdown_trigger_options_submenu_1, {
                    keyboard: false,
                    trigger: "hover-and-click",
                    dropdown_menu: document.getElementById(
                        "dropdown-menu-example-options-submenu-options-1"
                    ),
                });
            }

            var dropdown_trigger_options_submenu_2 = document.getElementById(
                "dropdown-menu-example-options-submenu-2"
            );
            if (dropdown_trigger_options_submenu_2) {
                createDropdown(dropdown_trigger_options_submenu_2, {
                    keyboard: false,
                    trigger: "hover-and-click",
                    dropdown_menu: document.getElementById(
                        "dropdown-menu-example-options-submenu-options-2"
                    ),
                });
            }

            var dropdown_trigger_disabled_options = document.getElementById(
                "dropdown-disable-options"
            );
            if (dropdown_trigger_disabled_options) {
                createDropdown(dropdown_trigger_disabled_options, {
                    keyboard: false,
                    dropdown_menu: document.getElementById("dropdown-menu-disable-options"),
                });
            }

            var dropdown_trigger_split_example1_options =
                document.getElementById("dropdown-split-example");
            if (dropdown_trigger_split_example1_options) {
                createDropdown(dropdown_trigger_split_example1_options, {
                    dropdown_menu: document.getElementById("dropdown-split-example-menu"),
                });
            }

            var dropdown_trigger_split_example2_options =
                document.getElementById("dropdown-split-example2");
            if (dropdown_trigger_split_example2_options) {
                createDropdown(dropdown_trigger_split_example2_options, {
                    dropdown_menu: document.getElementById("dropdown-split-example2-menu"),
                });
            }

            var dropdown_trigger_large_split_example_options = document.getElementById(
                "dropdown-large-split-example"
            );
            if (dropdown_trigger_large_split_example_options) {
                createDropdown(dropdown_trigger_large_split_example_options, {
                    dropdown_menu: document.getElementById("dropdown-large-split-example-menu"),
                });
            }

            [
                "dropdown-example",
                "dropdown-top-example",
                "dropdown-icon-right-example",
                "dropdown-with-tabs-example",
            ]
                .map((id) => document.getElementById(id))
                .filter((element) => {
                    return (
                        element &&
                        element.parentElement.querySelector(".tlp-dropdown-menu") instanceof
                            HTMLElement
                    );
                })
                .forEach((trigger) => createDropdown(trigger));
        }

        setTimeout(updatePreview, 10);
    });
})();
