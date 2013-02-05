/**
 * Copyright (c) Enalean SAS - 2013. All rights reserved
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

document.observe('dom:loaded', function () {
    var systray;

    if (! document.body.hasClassName('lab-mode')) {
        return;
    }

    systray = '<div class="systray">' +
                '<div class="systray_content">' +
                    '<img class="systray_icon" src="/themes/Tuleap/images/favicon.ico">' +
                    '<div class="systray_info">&nbsp;</div>' +
                '</div>' +
              '</div>';
    document.body.insert(systray);
});
