/**
 * Copyright (c) Enalean SAS - 2014. All rights reserved
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

(function ($) {

    function moveBreadcrumb() {
        var timeout = window.setInterval(function() {
            var origin = $('#trafficlights-breadcrumb');

            if (origin.length === 0) {
                return;
            }

            $('.breadcrumb').first().replaceWith(origin);
            origin.removeAttr('id');

            if ($('.breadcrumb > li').length === 0) {
                $('.breadcrumb').remove();
            }

            window.clearInterval(timeout);
        }, 10);
    }

    $(document).ready(moveBreadcrumb);

})(jQuery);