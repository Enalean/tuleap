/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

/* global jQuery:readonly codendi:readonly UserAutoCompleter:readonly */

/**
 * Script of the approval table reminder
 */

(function ($, codendi) {
    $(document).ready(function () {
        var table = $("#docman_approval_table_create_add_reviewers");

        if (table.length > 0) {
            var userAutocomplete = new UserAutoCompleter("user_list", codendi.imgroot, true);
            userAutocomplete.registerOnLoad();

            if ($("#approval_table_reminder_checkbox").is(":checked") === false) {
                $("#approval_table_occurence_form").hide();
            }

            $("#approval_table_reminder_checkbox").click(function () {
                $("#approval_table_occurence_form").slideToggle(0);
            });
        }
    });
})(jQuery, codendi || {});
