/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

/* global module:readonly */

/**
 * Handle lists filtering
 */
if (typeof module !== "undefined" && typeof module.exports !== "undefined") {
    module.exports = {
        listFilter: listFilterFactory,
    };
} else {
    var tuleap = tuleap || {};
    tuleap.core = tuleap.core || {};
    tuleap.core.listFilter = listFilterFactory;
}

function listFilterFactory() {
    var esc_keycode = 27;
    var list_element;
    var filter_element;

    var filterProjects = function (value) {
        var matching_elements = document.querySelectorAll(list_element);

        [].forEach.call(matching_elements, function (element) {
            if (caseInsensitiveContains(getChildTagAWithText(element), value)) {
                element.style.display = "inherit";
            } else {
                element.style.display = "none";
            }
        });
    };

    var getChildTagAWithText = function (parent) {
        var elements_tag_a = parent.getElementsByTagName("a");
        for (var i = 0, n = elements_tag_a.length; i < n; i++) {
            if (elements_tag_a[i].textContent) {
                return elements_tag_a[i].textContent;
            }
        }
    };

    var caseInsensitiveContains = function (element, value) {
        return element.toUpperCase().indexOf(value.toUpperCase()) >= 0;
    };

    var clearFilterProjects = function () {
        filter_element.value = "";
        filterProjects("");
    };

    var bindClickEventOnFilter = function (filter_element_selected) {
        if (filter_element_selected) {
            filter_element_selected.addEventListener("click", function (event) {
                event.stopPropagation();
            });
        }
    };

    var bindKeyUpEventOnFilter = function (filter_element_selected) {
        if (filter_element_selected) {
            filter_element_selected.addEventListener("keyup", function (event) {
                if (event.keyCode === esc_keycode) {
                    clearFilterProjects();
                } else {
                    filterProjects(filter_element_selected.value);
                }
            });
        }
    };

    var bindInputEventOnFilter = function (filter_element_selected) {
        if (filter_element_selected) {
            filter_element_selected.addEventListener("input", function (event) {
                if (event.target && event.target.value === "") {
                    clearFilterProjects();
                }
            });
        }
    };

    var init = function (filter_element_selected, list_element_selector) {
        filter_element = filter_element_selected;
        list_element = list_element_selector;

        bindClickEventOnFilter(filter_element_selected);
        bindKeyUpEventOnFilter(filter_element_selected);
        bindInputEventOnFilter(filter_element_selected);
    };

    return { init: init };
}
