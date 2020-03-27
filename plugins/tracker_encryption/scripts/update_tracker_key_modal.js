/**
 * Copyright (c) STMicroelectronics, 2016. All Rights Reserved.
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

jQuery(document).ready(function ($) {
    if (document.getElementById("tracker-key")) {
        var tracker_key = document.getElementById("tracker-key").textContent;
        $("#submit_form").on("click", function (e) {
            e.preventDefault();
            if (tracker_key == "" || $("#tracker-key").val() == tracker_key) {
                $("form").submit();
            } else {
                $("#confirm").modal("show");
            }
        });
    }
});
