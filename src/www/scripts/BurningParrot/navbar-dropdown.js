/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

export { init };

const escape_code_value = 27,
    nav_dropdown_content_classname = "nav-dropdown-content",
    nav_dropdown_link_classname = "nav-dropdown-link",
    nav_dropdown_content_hidden_classname = "nav-dropdown-content-hidden",
    nav_dropdown_content_disappear_classname = "nav-dropdown-content-disappear",
    nav_dropdown_content_visible_classname = "nav-dropdown-content-visible";

function init() {
    bindToggleDropdown();
    bindCloseOnClickOutsideDropdown();
    bindCloseOnEscape();
}

function bindToggleDropdown() {
    const nav_dropdown_links = document.getElementsByClassName(nav_dropdown_link_classname);

    for (const nav_dropdown_link of nav_dropdown_links) {
        nav_dropdown_link.addEventListener("click", () => {
            toggleDropdown(nav_dropdown_link.dataset.navDropdownContentId);
        });
    }
}

function bindCloseOnClickOutsideDropdown() {
    document.addEventListener("click", event => {
        const target = event.target;

        if (
            !findClosest(target, nav_dropdown_content_classname) &&
            !findClosest(target, nav_dropdown_link_classname)
        ) {
            hideAllDropdowns();
        }
    });
}

function bindCloseOnEscape() {
    document.addEventListener("keyup", event => {
        const target = event.target;

        if (
            target.tagName.toLowerCase() === "input" &&
            findAncestor(target, nav_dropdown_content_classname)
        ) {
            return;
        }

        if (event.keyCode === escape_code_value) {
            hideAllDropdowns();
        }
    });
}

function toggleDropdown(id) {
    hideOthersDropdowns(id);
    const dropdown = document.getElementById(id);

    if (dropdown.classList.contains(nav_dropdown_content_hidden_classname)) {
        dropdown.classList.remove(nav_dropdown_content_hidden_classname);
    }

    if (dropdown.classList.contains(nav_dropdown_content_visible_classname)) {
        dropdown.classList.remove(nav_dropdown_content_visible_classname);
        dropdown.classList.add(nav_dropdown_content_disappear_classname);
    } else {
        dropdown.classList.add(nav_dropdown_content_visible_classname);
        dropdown.classList.remove(nav_dropdown_content_disappear_classname);
    }
}

function hideOthersDropdowns(id) {
    const nav_dropdown_contents = document.getElementsByClassName(nav_dropdown_content_classname);

    for (const nav_dropdown_content of nav_dropdown_contents) {
        if (nav_dropdown_content.id !== id) {
            nav_dropdown_content.classList.remove(nav_dropdown_content_visible_classname);
            nav_dropdown_content.classList.add(nav_dropdown_content_disappear_classname);
        }
    }
}

function hideAllDropdowns() {
    const nav_dropdown_contents = document.getElementsByClassName(nav_dropdown_content_classname);

    for (const nav_dropdown_content of nav_dropdown_contents) {
        nav_dropdown_content.classList.remove(nav_dropdown_content_visible_classname);
        nav_dropdown_content.classList.add(nav_dropdown_content_disappear_classname);
    }
}

function findClosest(element, classname) {
    if (hasClassNamed(element, classname)) {
        return element;
    }

    return findAncestor(element, classname);
}

function findAncestor(element, classname) {
    while ((element = element.parentElement) && !hasClassNamed(element, classname)) {}
    return element;
}

function hasClassNamed(element, classname) {
    if (element.classList) {
        return element.classList.contains(classname);
        // IE11 SVG elements don't have classList
    } else if (element.getAttribute("class")) {
        return element.getAttribute("class").indexOf(classname) !== -1;
    }
}
