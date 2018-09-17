/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

(function loadCodeMirrorEditors() {
    var demo_panels = document.querySelectorAll(".demo");

    [].forEach.call(demo_panels, function(demo_panel) {
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
            scrollbarStyle: "overlay"
        });
        editor.on("change", function() {
            clearTimeout(delay);
            delay = setTimeout(updatePreview, 300);
        });

        function updatePreview() {
            example.innerHTML = editor.getValue();
            var datepickers = example.querySelectorAll(".tlp-input-date");
            [].forEach.call(datepickers, function(datepicker) {
                tlp.datePicker(datepicker);
            });

            var filters = example.querySelectorAll(".tlp-search[data-target-table-id]");
            [].forEach.call(filters, function(filter) {
                tlp.filterInlineTable(filter);
            });

            tlp.select2(document.querySelector("#area-select2"), {
                placeholder: "Choose an area",
                allowClear: true
            });
            tlp.select2(document.querySelector("#area-select2-adjusted"), {
                placeholder: "Choose an area",
                allowClear: true
            });
            tlp.select2(document.querySelector("#area-without-autocomplete"), {
                placeholder: "Choose an area",
                allowClear: true,
                minimumResultsForSearch: Infinity
            });
            tlp.select2(document.querySelector("#area-select2-help"), {
                placeholder: "Choose an area",
                allowClear: true
            });
            tlp.select2(document.querySelector("#area-select2-mandatory"), {
                placeholder: "Choose an area"
            });
            tlp.select2(document.querySelector("#area-select2-disabled"));
            tlp.select2(document.querySelector("#area-select2-error"), {
                placeholder: "Choose an area",
                allowClear: true
            });
            tlp.select2(document.querySelector("#area-select2-small"), {
                placeholder: "Choose an area",
                allowClear: true
            });
            tlp.select2(document.querySelector("#area-select2-large"), {
                placeholder: "Choose an area",
                allowClear: true
            });

            tlp.select2(document.querySelector("#types-select2"), {
                placeholder: "Choose a type",
                allowClear: true
            });
            tlp.select2(document.querySelector("#typess-select2"), {
                placeholder: "Choose a type",
                allowClear: true
            });
            tlp.select2(document.querySelector("#types-select2-adjusted"), {
                placeholder: "Choose a type",
                allowClear: true
            });
            tlp.select2(document.querySelector("#types-select2-help"), {
                placeholder: "Choose a type",
                allowClear: true
            });
            tlp.select2(document.querySelector("#types-select2-mandatory"), {
                placeholder: "Choose a type"
            });
            tlp.select2(document.querySelector("#types-select2-disabled"), {
                placeholder: "Choose a type",
                allowClear: true
            });
            tlp.select2(document.querySelector("#types-select2-error"), {
                placeholder: "Choose a type",
                allowClear: true
            });
            tlp.select2(document.querySelector("#types-select2-small"), {
                placeholder: "Choose a type",
                allowClear: true
            });
            tlp.select2(document.querySelector("#types-select2-large"), {
                placeholder: "Choose a type",
                allowClear: true
            });
            tlp.select2(document.querySelector("#append-select2"), {
                placeholder: "Choose an area",
                allowClear: true
            });
            tlp.select2(document.querySelector("#append-select2-small"), {
                placeholder: "Choose an area",
                allowClear: true
            });
            tlp.select2(document.querySelector("#append-select2-large"), {
                placeholder: "Choose an area",
                allowClear: true
            });
            tlp.select2(document.querySelector("#select2-prepend"), {
                placeholder: "Choose an area",
                allowClear: true
            });
            tlp.select2(document.querySelector("#select2-prepend-small"), {
                placeholder: "Choose an area",
                allowClear: true
            });
            tlp.select2(document.querySelector("#select2-prepend-large"), {
                placeholder: "Choose an area",
                allowClear: true
            });

            var modal_buttons = example.querySelectorAll("[data-target^=modal-]");
            [].forEach.call(modal_buttons, function(button) {
                var modal = tlp.modal(document.getElementById(button.dataset.target), {});
                button.addEventListener("click", function() {
                    modal.toggle();
                });
            });

            var dropdown_trigger_options = document.getElementById("dropdown-example-options");
            if (dropdown_trigger_options) {
                tlp.dropdown(dropdown_trigger_options, {
                    keyboard: false,
                    dropdown_menu: document.getElementById("dropdown-menu-example-options")
                });
            }

            [
                "dropdown-example",
                "dropdown-right-example",
                "dropdown-top-example",
                "dropdown-top-right-example",
                "dropdown-icon-right-example",
                "dropdown-with-tabs-example"
            ]
                .map(id => document.getElementById(id))
                .filter(element => Boolean(element))
                .forEach(trigger => tlp.dropdown(trigger));
        }
        setTimeout(updatePreview, 10);
    });
})();
