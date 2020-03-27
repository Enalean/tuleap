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

/* global codendi:readonly */

var tuleap = tuleap || {};

(function ($) {
    /**
     * @see http://tarruda.github.io/bootstrap-datetimepicker/
     */
    tuleap.dateTimePicker = {
        init: function () {
            $(".tuleap_field_date").datetimepicker({
                language: codendi.locale,
                pickTime: false,
            });

            $(".tuleap_field_datetime").datetimepicker({
                language: codendi.locale,
                pickTime: true,
                pickSeconds: false,
            });
        },
    };

    $(document).ready(function () {
        tuleap.dateTimePicker.init();
    });
})(window.jQuery);
