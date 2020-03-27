/*
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
    var svn_paths_textarea, svn_checkbox;

    function changeSVNPathsTextareVisibility() {
        if (svn_checkbox.is(":checked")) {
            svn_paths_textarea.show();
        } else {
            svn_paths_textarea.hide();
        }
    }

    $(document).ready(function () {
        svn_paths_textarea = $("#hudson_svn_paths");
        svn_checkbox = $("#hudson_use_svn_trigger");

        changeSVNPathsTextareVisibility();

        svn_checkbox.change(function () {
            changeSVNPathsTextareVisibility();
        });
    });
})(jQuery);
