/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
 *
 * Originally written by Nicolas Terray, 2008
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

/* global jQuery:readonly */

(function ($) {
    function init() {
        $(".tracker_field_permissionsonartifact").each(function (index, element) {
            disablePermissionsFieldIfNeeded(element);
            bindSwitch(element);
        });
    }

    function disablePermissionsFieldIfNeeded(element) {
        if ($("input[type='checkbox']", element).is(":not(:checked)")) {
            $(element).siblings("select").attr("disabled", "disabled");
        }
    }

    function bindSwitch(element) {
        $("input[type='checkbox']", element).bind("change", function () {
            togglePermissionsField(element);
        });
    }

    function togglePermissionsField(element) {
        if ($("input[type='checkbox']", element).is(":not(:checked)")) {
            $(element).siblings("select").attr("disabled", "disabled");
        } else {
            $(element).siblings("select").removeAttr("disabled");
        }
    }

    $(document).ready(function () {
        init();
    });
})(jQuery);
