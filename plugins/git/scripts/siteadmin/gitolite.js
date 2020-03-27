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

import { filterInlineTable } from "tlp";
import tuleap from "tuleap";

document.addEventListener("DOMContentLoaded", () => {
    const bindProjectSelectors = () => {
        const project_selectors = document.querySelectorAll(".gitolite-project-selector");
        [].forEach.call(project_selectors, function (project_selector) {
            tuleap.autocomplete_projects_for_select2(project_selector, {
                include_private_projects: true,
            });
        });
    };

    const bindFilter = () => {
        const filter = document.getElementById("filter-projects");
        if (filter) {
            filterInlineTable(filter);
        }
    };

    const bindToggleRevokeSelectedButton = () => {
        const selectboxes = document.querySelectorAll(
            '#allowed-projects-list input[type="checkbox"]'
        );

        if (!selectboxes) {
            return;
        }

        selectboxes.forEach((selectbox) => {
            selectbox.addEventListener("click", () => {
                if (
                    document.querySelectorAll(
                        '#allowed-projects-list input[type="checkbox"]:not(#check-all):checked'
                    ).length > 0
                ) {
                    document.getElementById("revoke-project").removeAttribute("disabled");
                } else {
                    document.getElementById("revoke-project").setAttribute("disabled", "disabled");
                }
            });
        });
    };

    const bindSelectAllCheckbox = () => {
        const check_all_selectbox = document.getElementById("check-all");
        const project_selectboxes = document.querySelectorAll(
            '#allowed-projects-list input[type="checkbox"]:not(#check-all)'
        );

        if (!check_all_selectbox || !project_selectboxes) {
            return;
        }

        check_all_selectbox.addEventListener("click", () => {
            if (check_all_selectbox.checked) {
                project_selectboxes.forEach((selectbox) => {
                    selectbox.checked = true;
                });
            } else {
                project_selectboxes.forEach((selectbox) => {
                    selectbox.checked = false;
                });
            }
        });

        project_selectboxes.forEach((selectbox) => {
            selectbox.addEventListener("click", () => {
                if (
                    document.querySelectorAll(
                        '#allowed-projects-list input[type="checkbox"]:not(#check-all):not(:checked)'
                    ).length > 0
                ) {
                    check_all_selectbox.checked = false;
                }
            });
        });
    };

    bindSelectAllCheckbox();
    bindToggleRevokeSelectedButton();
    bindProjectSelectors();
    bindFilter();
});
