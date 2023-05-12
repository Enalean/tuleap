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

import { select2, datePicker } from "tlp";
import { createPopover } from "@tuleap/tlp-popovers";
import { createModal } from "@tuleap/tlp-modal";
import { createDropdown } from "@tuleap/tlp-dropdown";
import CodeMirror from "codemirror";
import "codemirror/mode/htmlmixed/htmlmixed";
import "codemirror/addon/scroll/simplescrollbars";
import { sanitize } from "dompurify";
import "@tuleap/tlp-relative-date";
import { filterInlineTable } from "@tuleap/filter-inline-table";
import { createListPicker } from "@tuleap/list-picker";
import { createLazybox } from "@tuleap/lazybox";

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
                    items_template_formatter: (html, value_id, option_label) => {
                        if (value_id === "103" || value_id === "108") {
                            return html`<i class="fa-solid fa-fw fa-user-slash"></i>
                                ${option_label}`;
                        }
                        return html`<i class="fa-solid fa-fw fa-user"></i> ${option_label}`;
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

                createListPicker(document.querySelector("#list-picker-msb-none"), {
                    none_value: "100",
                });

                createListPicker(document.querySelector("#list-picker-msb-avatars"), {
                    placeholder: "Choose GoT characters",
                    is_filterable: true,
                    items_template_formatter: (html, value_id, option_label) => {
                        if (value_id === "103" || value_id === "108") {
                            return html`
                                <i class="fas fa-fw fa-user-slash"></i>
                                ${option_label}
                            `;
                        }
                        return html`
                            <i class="fas fa-fw fa-user"></i>
                            ${option_label}
                        `;
                    },
                });
            }
            if (example.id === "example-lazybox-") {
                initLinkSelector();
                initMultiUserLinkSelector();
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

function initLinkSelector() {
    const ADDITIONAL_ITEM_ID = 105;

    const item_105 = {
        value: {
            id: ADDITIONAL_ITEM_ID,
            color: "graffiti-yellow",
            xref: "story #105",
            title: "Do more stuff",
        },
        is_disabled: false,
    };
    const items = [
        {
            value: { id: 101, color: "acid-green", xref: "story #101", title: "Do this" },
            is_disabled: false,
        },
        {
            value: { id: 102, color: "fiesta-red", xref: "story #102", title: "Do that" },
            is_disabled: false,
        },
        {
            value: { id: 103, color: "deep-blue", xref: "story #103", title: "And that too" },
            is_disabled: true,
        },
    ];
    const recent_items = [
        {
            value: {
                id: 106,
                color: "lake-placid-blue",
                xref: "request #106",
                title: "Please fix",
            },
            is_disabled: false,
        },
        {
            value: {
                id: 107,
                color: "ocean-turquoise",
                xref: "request #107",
                title: "It does not work",
            },
            is_disabled: false,
        },
    ];

    const items_group = {
        label: "Matching items",
        empty_message: "No matching item",
        is_loading: false,
        items,
    };
    const recent_group = {
        label: "Recent items",
        empty_message: "No recent item",
        is_loading: false,
        items: recent_items,
    };

    const mount_point = document.getElementById("lazybox-link-selector");
    const link_selector = createLazybox(document);
    link_selector.options = {
        is_multiple: false,
        placeholder: "Please select an item to link",
        search_input_placeholder: "Type a number",
        new_item_button_label: "→ Create a new item…",
        new_item_callback: () => {
            // Do nothing
        },
        templating_callback: (html, item) =>
            html`<span class="tlp-badge-${item.value.color} doc-link-selector-badge">
                    ${item.value.xref}
                </span>
                ${item.value.title}`,
        selection_callback: () => {
            // Do nothing
        },
        search_input_callback: (query) => {
            if (query === "") {
                link_selector.replaceDropdownContent([items_group, recent_group]);
                return;
            }
            const lowercase_query = query.toLowerCase();

            if (lowercase_query === String(ADDITIONAL_ITEM_ID)) {
                link_selector.replaceDropdownContent([{ ...items_group, items: [item_105] }]);
                return;
            }
            const matching_items = items.filter(
                (item) =>
                    String(item.value.id).includes(lowercase_query) ||
                    item.value.title.toLowerCase().includes(lowercase_query)
            );
            const matching_recent = recent_items.filter((item) =>
                item.value.title.toLowerCase().includes(lowercase_query)
            );
            const matching_items_group = { ...items_group, items: matching_items };
            const matching_recent_group = { ...recent_group, items: matching_recent };
            link_selector.replaceDropdownContent([matching_items_group, matching_recent_group]);
        },
    };
    link_selector.replaceDropdownContent([items_group, recent_group]);
    mount_point.replaceWith(link_selector);
}

function initMultiUserLinkSelector() {
    const users = [
        {
            value: { id: 102, display_name: "Johnny Cash (jocash)" },
            is_disabled: false,
        },
        {
            value: { id: 102, display_name: "Joe l'Asticot (jolasti)" },
            is_disabled: false,
        },
        {
            value: { id: 103, display_name: "John doe (jdoe)" },
            is_disabled: false,
        },
        {
            value: { id: 104, display_name: "Joe the hobo (johobo)" },
            is_disabled: true,
        },
    ];
    const users_group = {
        label: "Matching users",
        empty_message: "No user found",
        is_loading: false,
        items: [],
    };
    const recent_users = [
        { value: { id: 105, display_name: "Jon Snow (jsnow)" }, is_disabled: false },
        { value: { id: 106, display_name: "Joe Dalton (jdalton)" }, is_disabled: false },
    ];
    const recent_group = {
        label: "Recent users",
        empty_message: "No user found",
        is_loading: false,
        items: [],
    };

    const mount_point = document.getElementById("lazybox-users-selector");
    const users_lazybox = createLazybox(document);
    users_lazybox.options = {
        is_multiple: true,
        placeholder: "Search users by names",
        templating_callback: (html, item) => html`
            <span class="doc-multiple-lazybox-user-with-avatar">
                <div class="tlp-avatar-mini"></div>
                ${item.value.display_name}
            </span>
        `,
        selection_callback: () => {
            // Do nothing
        },
        search_input_callback: (query) => {
            if (query === "") {
                users_lazybox.replaceDropdownContent([users_group]);
                return;
            }
            const lowercase_query = query.toLowerCase();
            const matching_users = users.filter((user) =>
                user.value.display_name.toLowerCase().includes(lowercase_query)
            );
            const matching_recent = recent_users.filter((user) =>
                user.value.display_name.toLowerCase().includes(lowercase_query)
            );
            const matching_users_group = { ...users_group, items: matching_users };
            const matching_recent_group = { ...recent_group, items: matching_recent };
            users_lazybox.replaceDropdownContent([matching_users_group, matching_recent_group]);
        },
    };
    users_lazybox.replaceDropdownContent([users_group]);
    users_lazybox.replaceSelection([users[0]]);
    mount_point.replaceWith(users_lazybox);
}
