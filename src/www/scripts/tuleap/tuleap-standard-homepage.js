/**
 * Copyright (c) Enalean SAS - 2015. All rights reserved
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

/* global jQuery:readonly $clamp:readonly */
(function ($) {
    $(document).ready(function () {
        $(".news blockquote > div").each(function () {
            $clamp($(this)[0], { clamp: 2 });
        });

        $(".screenshot-right").viewportChecker({
            classToAdd: "visible animated slideInRight",
            offset: 200,
        });

        $(".screenshot-left").viewportChecker({
            classToAdd: "visible animated slideInLeft",
            offset: 200,
        });
    });
})(jQuery);
