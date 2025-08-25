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

import { openAllTargetModalsOnClick } from "@tuleap/tlp-modal";
import { datePicker } from "@tuleap/tlp-date-picker";
import { filterInlineTable } from "@tuleap/filter-inline-table";
import CKEDITOR from "ckeditor4";
import tuleap from "tuleap";
import {
    autocomplete_users_for_select2,
    autocomplete_projects_for_select2,
} from "@tuleap/autocomplete-for-select2";

document.addEventListener("DOMContentLoaded", function () {
    const date_picker_elements = document.querySelectorAll(".tlp-input-date");

    for (const element of date_picker_elements) {
        datePicker(element);
    }

    const ckeditor_selector = document.querySelectorAll(".project-over-quota-massmail-body");

    for (const ckeditor_element of ckeditor_selector) {
        CKEDITOR.replace(ckeditor_element.id, tuleap.ckeditor.config);
    }

    const project_selectors = document.querySelectorAll(".project-autocompleter");
    for (const project_selector of project_selectors) {
        autocomplete_projects_for_select2(project_selector, {
            include_private_projects: true,
        });
    }

    const user_selectors = document.querySelectorAll(".user-autocompleter");
    for (const user_selector of user_selectors) {
        autocomplete_users_for_select2(user_selector, {
            internal_users_only: true,
        });
    }

    openAllTargetModalsOnClick(document, "[data-statistics-button]");

    const data_export_contents = document.querySelectorAll(".siteadmin-export-data");
    const data_export_inputs = document.querySelectorAll('input[name="data-export-content"]');
    for (const data_export_input of data_export_inputs) {
        data_export_input.addEventListener("change", function (event) {
            const content_value = event.target.value;

            for (const content_to_disappear of data_export_contents) {
                if (content_to_disappear.id !== content_value) {
                    content_to_disappear.classList.add("siteadmin-export-data-disappear");
                } else {
                    content_to_disappear.classList.remove("siteadmin-export-data-disappear");
                }
            }

            const inputs = document.querySelectorAll("input[value=" + content_value + "]");
            for (const input of inputs) {
                input.checked = input.value === content_value;
            }
        });
    }

    const filter_project_over_quota = document.getElementById("filter-table-project-over-quota");
    if (filter_project_over_quota) {
        filterInlineTable(filter_project_over_quota);
    }
});
