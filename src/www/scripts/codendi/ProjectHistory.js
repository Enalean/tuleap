/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
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

var ProjectHistory = Class.create({
    initialize: function () {
        var title = $('history_search_title');
        title.observe('click', this.toggleForm);
        // We may make the form hidden by default
        //$('project_history_search').hide();
    },
    toggleForm: function() {
        // Toggle search form
        $('project_history_search').toggle();
        // Switch icon plus/minus
        var icon = $('toggle_form_icon');
        if (icon.src.indexOf('toggle_plus.png') != -1) {
            icon.src = icon.src.replace('toggle_plus.png', 'toggle_minus.png');
        } else {
            icon.src = icon.src.replace('toggle_minus.png', 'toggle_plus.png');
        }
    }
});