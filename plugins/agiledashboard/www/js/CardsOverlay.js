/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * This script is responsible for the edition of cards directly
 * on the Agile Dashboard.
 */

var tuleap = tuleap || {};
tuleap.agiledashboard = tuleap.agiledashboard || { };

(function ($) {
    var overlay_window;

    function displayOverlay(event) {
        event.preventDefault();

        var artifact_id = $(this).attr('data-artifact-id');
        var params = {
            aid  : artifact_id,
            func : 'show-in-overlay'
        };

        overlay_window.activateWindow({
                href        : codendi.tracker.base_url + '?' + $.param(params),
                title       : codendi.locales['agiledashboard']['edit_card'],
                iframeEmbed : true,
            });
    }

    tuleap.agiledashboard.CardsOverlay = function () {
        $('li > a.edit-card').each(function(){
            $(this).click(displayOverlay);
        });
    };

    $(document).ready(function () {
        tuleap.agiledashboard.CardsOverlay();
        overlay_window = new lightwindow({
            resizeSpeed: 10,
            delay: 0,
            finalAnimationDuration: 0,
            finalAnimationDelay: 0
        });
    });
})(jQuery);