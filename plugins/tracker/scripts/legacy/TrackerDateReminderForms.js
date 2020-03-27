/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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
 * This script display a hidden div that contains new reminder submission button then listen to any
 * reminder creation request and delegate process ot the right function within tracker class.
 */

/* global jQuery:readonly */

(function ($) {
    $(document).ready(function () {
        var tracker_reminder_element = $("#tracker_reminder");

        if (tracker_reminder_element !== undefined) {
            tracker_reminder_element.show();
            $("#add_reminder").click(function () {
                $.ajax({
                    url: "?func=display_reminder_form",
                    type: "get",
                    success: function (html_form) {
                        tracker_reminder_element.html(html_form);
                    },
                });
            });
        }
    });
})(jQuery);
