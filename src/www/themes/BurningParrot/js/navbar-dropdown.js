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

/**
 * Handle navbar dropdown events
 */
(function() {
    const escape_code_value = 27;

    const nav_dropdow_selector_name           = 'nav-dropdown';
    const dropdow_selector_name               = 'nav-dropdown-content';
    const nav_dropdown_visible_name           = 'nav-dropdown-visible';
    const nav_dropdown_content_visible_name   = 'nav-dropdown-content-visible';
    const nav_dropdown_content_hidden_name    = 'nav-dropdown-content-hidden';
    const nav_dropdown_content_disappear_name = 'nav-dropdown-content-disappear';
    const nav_link_text_name                  = 'nav-link-text';
    const nav_link_icon_name                  = 'nav-link-icon';
    const nav_link_icon_dropdown_name         = 'nav-link-icon-dropdown';

    const dropdown_selector = '.nav-dropdown-content';

    document.addEventListener('DOMContentLoaded', function() {
        displayDropdown();

        document.addEventListener('click', function(event) {
            var target = event.target;
            if (! target.classList.contains(nav_dropdow_selector_name)
                && ! target.classList.contains(nav_link_text_name)
                && ! target.classList.contains(nav_link_icon_name)
                && ! target.classList.contains(nav_link_icon_dropdown_name)
            ) {
                removeDropdown();
            }
        });

        document.addEventListener('keyup', function(event) {
            if (event.keyCode == escape_code_value) {
                removeDropdown();
            }
        });
    });

    function displayDropdown() {
        var nav_dropdow_elements = document.getElementsByClassName(nav_dropdow_selector_name);

        for (var i = 0, n = nav_dropdow_elements.length; i < n; i++) {
            var nav_dropdow_element          = nav_dropdow_elements[i];
            var nav_dropdow_element_children = nav_dropdow_element.children;

            for (var j = 0, m = nav_dropdow_element_children.length; j < m; j++) {
                nav_dropdow_element_children[j].addEventListener('click', function(event) {
                    var target = event.target;
                    if (! target.classList.contains(nav_link_text_name)
                        && ! target.classList.contains(nav_link_icon_name)
                        && ! target.classList.contains(nav_link_icon_dropdown_name)
                    ) {
                        event.stopPropagation();
                    }
                });
            }

            nav_dropdow_element.addEventListener('click', function(event) {
                var target                  = event.target;
                var dropdown_element        = getDropdownElement(target);
                var dropdown_element_parent = dropdown_element.parentNode;

                if (dropdown_element.classList.contains(nav_dropdown_content_hidden_name)) {
                    dropdown_element.classList.remove(nav_dropdown_content_hidden_name);
                }

                if (dropdown_element.classList.contains(nav_dropdown_content_visible_name)) {
                    dropdown_element.classList.add(nav_dropdown_content_disappear_name);
                    dropdown_element.classList.remove(nav_dropdown_content_visible_name);
                    dropdown_element_parent.classList.remove(nav_dropdown_visible_name);
                } else {
                    dropdown_element.classList.remove(nav_dropdown_content_disappear_name);
                    dropdown_element.classList.add(nav_dropdown_content_visible_name);
                    dropdown_element_parent.classList.add(nav_dropdown_visible_name);
                }
            });
        }
    }

    function removeDropdown() {
        var nav_dropdow_elements = document.getElementsByClassName(nav_dropdow_selector_name);
        var dropdow_elements     = document.getElementsByClassName(dropdow_selector_name);
        for (var i = 0, n = nav_dropdow_elements.length; i < n; i++) {
            nav_dropdow_elements[i].classList.remove(nav_dropdown_visible_name);
        }

        for (i = 0, n = dropdow_elements.length; i < n; i++) {
            dropdow_elements[i].classList.add(nav_dropdown_content_disappear_name);
            dropdow_elements[i].classList.remove(nav_dropdown_content_visible_name);
        }
    }

    function getDropdownElement(element) {
        var dropdown_element;
        if (element.classList.contains(nav_dropdow_selector_name)) {
            dropdown_element = element.querySelector(dropdown_selector);
        } else {
            dropdown_element = findAncestor(element, nav_dropdow_selector_name).querySelector(dropdown_selector);
        }
        return dropdown_element;
    }

    function findAncestor(element, cls) {
        while ((element = element.parentElement) && ! element.classList.contains(cls)) {}
        return element;
    }
})();