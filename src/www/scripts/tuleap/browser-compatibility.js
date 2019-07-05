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

var tuleap = tuleap || {};

// Search for a class in loaded stylesheets
(function() {
    tuleap.browserCompatibility = {
        isIE7: function() {
            if (
                typeof navigator == "undefined" ||
                typeof navigator.appVersion == "undefined" ||
                navigator.appVersion.indexOf("MSIE 7.") != -1
            ) {
                return true;
            }

            return false;
        },

        isIE: function() {
            var user_agent = window.navigator.userAgent;

            var msie = user_agent.indexOf("MSIE ");
            if (msie > 0) {
                return true;
            }

            var trident = user_agent.indexOf("Trident/");
            if (trident > 0) {
                return true;
            }

            var edge = user_agent.indexOf("Edge/");
            if (edge > 0) {
                return true;
            }

            return false;
        }
    };
})();
