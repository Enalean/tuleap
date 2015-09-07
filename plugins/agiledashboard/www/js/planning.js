/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
tuleap.agiledashboard = tuleap.agiledashboard || { };

// inline-blocks may have different heights (depends on the content)
// so align them to have sexy homepage
tuleap.agiledashboard.align_short_access_heights = function() {
    $$('.ad_index_plannings').map(tuleap.agiledashboard.fixHeightOfShortAccessBox);
}

tuleap.agiledashboard.resetHeightOfShortAccessBox = function(list_of_plannings) {
    list_of_plannings.childElements().invoke('setStyle', { height: 'auto' });
}
tuleap.agiledashboard.fixHeightOfShortAccessBox = function(list_of_plannings) {
    var max_height = list_of_plannings.childElements().inject(0, function(m, v) {
        return Math.max(m, v.getHeight());
    });
    list_of_plannings.childElements().invoke('setStyle', {height: max_height+'px'});
}
