/**
 * Copyright (c) Enalean SAS - 2014 - 2016. All rights reserved
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

var codendi = codendi || {};

(function ($, codendi) {
    $(document).ready(function () {
        var embedded_content = $("#embedded_content"),
            //eslint-disable-next-line @typescript-eslint/no-unused-vars
            rte = null,
            options = null;

        if (embedded_content.length > 0) {
            options = {
                toolbar: "advanced",
                onLoad: $.noop(),
                toggle: false,
                default_in_html: true,
            };

            rte = new codendi.RTE(embedded_content.attr("id"), options);
        }
    });
})(jQuery, codendi || {});
