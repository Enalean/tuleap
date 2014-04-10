/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

var tuleap = tuleap || { };
tuleap.tracker = tuleap.tracker || { };

(function ($) {
    tuleap.tracker.dateTimePicker = {

        init: function(selector) {

         $(selector).datetimepicker({
             language: codendi.locale,
             pickTime: false
             });
        }
    };

    $(document).ready(function () {
        if ($('#tracker_report_form').size() > 0) {
            tuleap.tracker.dateTimePicker.init('.tracker_artifact_field_date > span');
            return;
        }
        tuleap.tracker.dateTimePicker.init('.tracker_artifact_field_date');
    });

})(window.jQuery);