/*
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
import jQuery from "jquery";

(function ($) {
    $().ready(function () {
        function get_selected_ids(selected_elements) {
            var selected_ids = [];
            selected_elements.each(function (i, element) {
                selected_ids.push(element.value);
            });
            return selected_ids;
        }

        function add_selected_element_to_field(container, selected_elements) {
            var selected_ids = get_selected_ids(selected_elements);
            container.find(".forge_mw_hidden_selected_groups").val(function (i, val) {
                return selected_ids.concat(val.split(",")).join(",");
            });
        }

        function remove_selected_element_to_field(container, selected_elements) {
            var selected_ids = get_selected_ids(selected_elements);
            container.find(".forge_mw_hidden_selected_groups").val(function (i, val) {
                return $(val.split(",")).not(selected_ids).get();
            });
        }

        $(".forge_mw_add_ugroup").click(function () {
            var container = $(this).parent(".forge_mw_btn").parent();
            var selected_elements = container.find(".forge_mw_available_groups option:selected");
            add_selected_element_to_field(container, selected_elements);
            return !selected_elements
                .remove()
                .appendTo(container.find(".forge_mw_selected_groups"));
        });
        $(".forge_mw_remove_ugroup").click(function () {
            var container = $(this).parent(".forge_mw_btn").parent();
            var selected_elements = container.find(".forge_mw_selected_groups option:selected");
            remove_selected_element_to_field(container, selected_elements);
            return !selected_elements
                .remove()
                .appendTo(container.find(".forge_mw_available_groups"));
        });
    });
})(jQuery);
