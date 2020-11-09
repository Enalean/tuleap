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

import { select2, createModal, datePicker, createPopover, createDropdown } from "tlp";
import CodeMirror from "codemirror";
import "codemirror/mode/htmlmixed/htmlmixed";
import "codemirror/addon/scroll/simplescrollbars";
import { sanitize } from "dompurify";
import "../../src/js/custom-elements/relative-date";
import { filterInlineTable } from "../../../../scripts/tuleap/tables/filter-inline-table";
import { createListPicker } from "../../../../scripts/list-picker/src/list-picker";

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

            select2(document.querySelector("#area-select2"), {
                placeholder: "Choose an area",
                allowClear: true,
            });
            select2(document.querySelector("#area-select2-adjusted"), {
                placeholder: "Choose an area",
                allowClear: true,
            });
            select2(document.querySelector("#area-without-autocomplete"), {
                placeholder: "Choose an area",
                allowClear: true,
                minimumResultsForSearch: Infinity,
            });
            select2(document.querySelector("#area-select2-help"), {
                placeholder: "Choose an area",
                allowClear: true,
            });
            select2(document.querySelector("#area-select2-mandatory"), {
                placeholder: "Choose an area",
            });
            select2(document.querySelector("#area-select2-disabled"));
            select2(document.querySelector("#area-select2-error"), {
                placeholder: "Choose an area",
                allowClear: true,
            });
            select2(document.querySelector("#area-select2-small"), {
                placeholder: "Choose an area",
                allowClear: true,
            });
            select2(document.querySelector("#area-select2-large"), {
                placeholder: "Choose an area",
                allowClear: true,
            });

            select2(document.querySelector("#types-select2"), {
                placeholder: "Choose a type",
                allowClear: true,
            });
            select2(document.querySelector("#typess-select2"), {
                placeholder: "Choose a type",
                allowClear: true,
            });
            select2(document.querySelector("#types-select2-adjusted"), {
                placeholder: "Choose a type",
                allowClear: true,
            });
            select2(document.querySelector("#types-select2-help"), {
                placeholder: "Choose a type",
                allowClear: true,
            });
            select2(document.querySelector("#types-select2-mandatory"), {
                placeholder: "Choose a type",
            });
            select2(document.querySelector("#types-select2-disabled"), {
                placeholder: "Choose a type",
                allowClear: true,
            });
            select2(document.querySelector("#types-select2-error"), {
                placeholder: "Choose a type",
                allowClear: true,
            });
            select2(document.querySelector("#types-select2-small"), {
                placeholder: "Choose a type",
                allowClear: true,
            });
            select2(document.querySelector("#types-select2-large"), {
                placeholder: "Choose a type",
                allowClear: true,
            });
            select2(document.querySelector("#append-select2"), {
                placeholder: "Choose an area",
                allowClear: true,
            });
            select2(document.querySelector("#append-select2-small"), {
                placeholder: "Choose an area",
                allowClear: true,
            });
            select2(document.querySelector("#append-select2-large"), {
                placeholder: "Choose an area",
                allowClear: true,
            });
            select2(document.querySelector("#select2-prepend"), {
                placeholder: "Choose an area",
                allowClear: true,
            });
            select2(document.querySelector("#select2-prepend-small"), {
                placeholder: "Choose an area",
                allowClear: true,
            });
            select2(document.querySelector("#select2-prepend-large"), {
                placeholder: "Choose an area",
                allowClear: true,
            });

            if (example.id === "example-list-picker-") {
                createListPicker(document.querySelector("#list-picker-sb"), {
                    placeholder: "Choose a value",
                    is_filterable: true,
                });

                createListPicker(document.querySelector("#list-picker-sb-with-optgroups"), {
                    placeholder: "Choose a value",
                    is_filterable: true,
                });

                createListPicker(document.querySelector("#list-picker-sb-disabled"), {
                    placeholder: "You can't choose any value yet",
                });

                createListPicker(document.querySelector("#list-picker-sb-error"), {
                    placeholder: "Choose a value",
                });

                createListPicker(document.querySelector("#list-picker-sb-avatars"), {
                    placeholder: "Choose a GoT character",
                    is_filterable: true,
                    items_template_formatter: (value_id, option_label) => {
                        if (value_id === "103" || value_id === "108") {
                            return `<i class="fas fa-fw fa-user-slash"></i> ${option_label}`;
                        }
                        return `<i class="fas fa-fw fa-user"></i> ${option_label}`;
                    },
                });
            }

            if (example.id === "example-multi-list-picker-") {
                createListPicker(document.querySelector("#list-picker-msb"), {
                    placeholder: "Choose some values in the list",
                });

                createListPicker(document.querySelector("#list-picker-msb-grouped"), {
                    placeholder: "Choose some values in the list",
                });

                createListPicker(document.querySelector("#list-picker-msb-disabled"), {
                    placeholder: "Choose some values in the list",
                });

                createListPicker(document.querySelector("#list-picker-msb-error"), {
                    placeholder: "Choose some values in the list",
                });

                createListPicker(document.querySelector("#list-picker-msb-avatars"), {
                    placeholder: "Choose GoT characters",
                    is_filterable: true,
                    items_template_formatter: (value_id, option_label) => {
                        if (value_id === "103" || value_id === "108") {
                            return `<i class="fas fa-fw fa-user-slash"></i> ${option_label}`;
                        }
                        return `<i class="fas fa-fw fa-user"></i> ${option_label}`;
                    },
                });
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

            var dropdown_trigger = document.getElementById("dropdown-example");
            if (dropdown_trigger) {
                createDropdown(dropdown_trigger);
            }

            var dropdown_trigger_options = document.getElementById("dropdown-example-options");
            if (dropdown_trigger_options) {
                createDropdown(dropdown_trigger_options, {
                    keyboard: false,
                    dropdown_menu: document.getElementById("dropdown-menu-example-options"),
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

            var dropdown_trigger_split_example1_options = document.getElementById(
                "dropdown-split-example"
            );
            if (dropdown_trigger_split_example1_options) {
                createDropdown(dropdown_trigger_split_example1_options, {
                    dropdown_menu: document.getElementById("dropdown-split-example-menu"),
                });
            }

            var dropdown_trigger_split_example2_options = document.getElementById(
                "dropdown-split-example2"
            );
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
                "dropdown-right-example",
                "dropdown-top-example",
                "dropdown-top-right-example",
                "dropdown-icon-right-example",
                "dropdown-with-tabs-example",
            ]
                .map((id) => document.getElementById(id))
                .filter((element) => Boolean(element))
                .forEach((trigger) => createDropdown(trigger));
        }
        setTimeout(updatePreview, 10);
    });
})();
