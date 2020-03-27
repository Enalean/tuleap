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

import jQuery from "jquery";

!(function ($) {
    var svn_checkbox, form;

    function changeSVNFormVisibility() {
        if (svn_checkbox.is(":checked")) {
            form.show();
        } else {
            form.hide();
        }
    }

    $(document).ready(function () {
        svn_checkbox = $("#hudson_use_plugin_svn_trigger_checkbox");
        form = $("#hudson_use_plugin_svn_trigger_form");

        changeSVNFormVisibility();

        svn_checkbox.change(function () {
            changeSVNFormVisibility();
        });
    });
})(jQuery);
