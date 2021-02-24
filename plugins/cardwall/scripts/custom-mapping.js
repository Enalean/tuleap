/*
 * Copyright Enalean (c) 2011, 2012, 2013, 2014 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

/* global jQuery:readonly */

/**
 * This script manage the admin/config of cardwall in tracker administration
 */
!(function ($) {
    $(document).ready(function () {
        var selector = ".cardwall_admin_ontop_mappings input[name^=custom_mapping]";
        $(selector).each(registerClickHandlerOnCustomMappingCheckbox);
    });

    function registerClickHandlerOnCustomMappingCheckbox() {
        $(this).on("click", toggleDisabledStateOfCorrespondingSelectboxField);
    }

    function toggleDisabledStateOfCorrespondingSelectboxField() {
        var select = $(this).parents("td").find("select");

        if (this.checked) {
            $(select).prop("disabled", false).focus();
        } else {
            $(select).prop("disabled", true);
        }
    }
})(jQuery);
