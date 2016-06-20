/**
 * Copyright (c) Enalean, 2014-2016. All Rights Reserved.
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
 * Setup the different filters of the AgileDashboard
 */

(function($) {
    $(document).ready(function() {
        var input_filter  = document.querySelector('.content-filter.open');
        var list_selector = '.milestone-content-open tr';
        var filter        = new tuleap.core.listFilter();

        filter.init(input_filter, list_selector);
    });

    $(document).ready(function() {
        var input_filter  = document.querySelector('.content-filter.closed');
        var list_selector = '.milestone-content-closed tr';
        var filter        = new tuleap.core.listFilter();

        filter.init(input_filter, list_selector);
    });
})(window.jQuery);