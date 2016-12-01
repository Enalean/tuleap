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

(function() {
    var sidebar_collapsers = document.querySelectorAll('.sidebar-collapser'),
        sidebar            = document.querySelector('.siteadmin-sidebar');

    bindSidebarEvent();

    function bindSidebarEvent() {
        sidebar.addEventListener('click', function(event) {
            var clicked_element                        = event.target,
                is_clicked_element_a_sidebar_collapser = isClickedElementASidebarCollapser(clicked_element);

            if (! is_clicked_element_a_sidebar_collapser && document.body.classList.contains('sidebar-fully-collpased')) {
                document.body.classList.remove('sidebar-fully-collpased');

            } else if (is_clicked_element_a_sidebar_collapser && ! document.body.classList.contains('sidebar-fully-collpased')) {
                document.body.classList.add('sidebar-fully-collpased');
            }
        });
    }

    function isClickedElementASidebarCollapser(clicked_element) {
        var is_clicked_element_a_sidebar_collapser = false;

        [].forEach.call(sidebar_collapsers, function(sidebar_collapser) {
            if (sidebar_collapser === clicked_element) {
                is_clicked_element_a_sidebar_collapser = true;
            }
        });

        return is_clicked_element_a_sidebar_collapser;
    }
})();
