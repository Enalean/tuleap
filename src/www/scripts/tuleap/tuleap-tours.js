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

/* global tuleap:readonly Tour:readonly jQuery:readonly */
/**
 * Handle Tuleap tours
 */
(function ($) {
    $(document).ready(function () {
        if (typeof tuleap.tours === "undefined") {
            return;
        }

        tuleap.tours.forEach(function (tour_options) {
            var tour;

            tour_options["onEnd"] = function (tour) {
                $.ajax({
                    type: "POST",
                    url: "/tour/end-tour.php",
                    data: {
                        tour_name: tour_options.name,
                        current_step: tour.getCurrentStep(),
                    },
                });
            };

            tour_options["onShown"] = function (tour) {
                $.ajax({
                    type: "POST",
                    url: "/tour/step-shown.php",
                    data: {
                        tour_name: tour_options.name,
                        current_step: tour.getCurrentStep(),
                    },
                });
            };

            tour = new Tour(tour_options);
            tour.init();
            tour.start();
        });
    });
})(jQuery);
