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

/**
 * Handle navbar dropdown events
 */
import { listFilter } from "../tuleap/listFilter.js";

export { init };

const nav_dropdow_selector_name = "nav-dropdown";
const nav_dropdow_projects_selector_name = "nav-dropdown-projects";
const nav_dropdown_content_visible_name = "nav-dropdown-content-visible";
const nav_dropdown_content_projects_filter_projects_name =
    "nav-dropdown-content-projects-filter-projects";
const nav_dropdown_content_projects_list_project_hover_name =
    "nav-dropdown-content-projects-list-project-hover";
const nav_dropdown_content_projects_list_project_name =
    "nav-dropdown-content-projects-list-project";
const nav_dropdown_content_projects_list_project_name_name =
    "nav-dropdown-content-projects-list-project-name";
const nav_dropdown_content_projects_list_project_name_hover_name =
    "nav-dropdown-content-projects-list-project-name-hover";
const nav_dropdown_content_projects_list_project_config_hover_name =
    "nav-dropdown-content-projects-list-project-config-hover";
const nav_dropdown_content_projects_list_project_config_name =
    "nav-dropdown-content-projects-list-project-config";

const dropdown_selector = ".nav-dropdown-content";
const nav_dropdown_content_projects_list_project_selector =
    ".nav-dropdown-content-projects-list-project";

const tab_code_value = 9;

function init() {
    if (!document.getElementById(nav_dropdown_content_projects_filter_projects_name)) {
        return;
    }

    initFilter();
    focusOnFilter();

    document.addEventListener("keyup", (event) => {
        if (event.keyCode === tab_code_value) {
            underlineProject(event);
        }
    });
}

function focusOnFilter() {
    const nav_dropdow_element = document.getElementById(nav_dropdow_projects_selector_name);

    nav_dropdow_element.addEventListener("click", (event) => {
        const target = event.target;
        const dropdown_element = getDropdownElement(target);

        if (dropdown_element.classList.contains(nav_dropdown_content_visible_name)) {
            document.getElementById(nav_dropdown_content_projects_filter_projects_name).focus();
        }
    });
}

function initFilter() {
    const input_filter = document.getElementById(
        nav_dropdown_content_projects_filter_projects_name
    );
    const filter = new listFilter();
    filter.init(input_filter, nav_dropdown_content_projects_list_project_selector);
    input_filter.focus();
}

function underlineProject(event) {
    removeUnderlineProject();
    const target = event.target;
    if (target.parentNode.classList.contains(nav_dropdown_content_projects_list_project_name)) {
        target.parentNode.classList.add(nav_dropdown_content_projects_list_project_hover_name);

        if (target.classList.contains(nav_dropdown_content_projects_list_project_name_name)) {
            target.classList.add(nav_dropdown_content_projects_list_project_name_hover_name);
        }
        if (target.classList.contains(nav_dropdown_content_projects_list_project_config_name)) {
            target.classList.add(nav_dropdown_content_projects_list_project_config_hover_name);
        }
    }
}

function removeUnderlineProject() {
    const elements = document.getElementsByClassName(
        nav_dropdown_content_projects_list_project_hover_name
    );
    for (let i = 0, n = elements.length; i < n; i++) {
        const children = elements[i].children;

        for (let j = 0, m = children.length; j < m; j++) {
            children[j].classList.remove(
                nav_dropdown_content_projects_list_project_name_hover_name
            );
            children[j].classList.remove(
                nav_dropdown_content_projects_list_project_config_hover_name
            );
        }
        elements[i].classList.remove(nav_dropdown_content_projects_list_project_hover_name);
    }
}

function getDropdownElement(element) {
    let dropdown_element;
    if (element.classList.contains(nav_dropdow_selector_name)) {
        dropdown_element = element.querySelector(dropdown_selector);
    } else {
        dropdown_element = element
            .closest("." + nav_dropdow_selector_name)
            .querySelector(dropdown_selector);
    }
    return dropdown_element;
}
