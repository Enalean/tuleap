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

(function ($) {
    $(document).ready(function () {
        $('.remove_followers').each( function() {
            var link = $(this);

            link.attr('href', link.attr('href')+'&challenge='+$('input[name=challenge]').val());

            link.click(function() {
                return confirm(link.data('message'));
            })
        });
    });
})(jQuery);