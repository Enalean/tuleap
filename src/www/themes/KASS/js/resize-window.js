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

!function($) {
    function resizeLogo() {
        var logo_size = '';
        if ($('.sidebar-nav').width() == 40 || $(document).width() <= 1210) {
            logo_size = '-small';
        }

        $('.logo').removeClass('logo-background logo-background-small').addClass('logo-background'+logo_size);
    }

    function headerResized() {
        $('.main').removeClass('big-nav huge-nav');
        $('.sidebar-nav').removeClass('big-nav huge-nav');

        if ($('.navbar').height() > 105) {
            $('.main').addClass('huge-nav');
            $('.sidebar-nav').addClass('huge-nav');
        } else if ($('.navbar').height() > 53) {
            $('.main').addClass('big-nav');
            $('.sidebar-nav').addClass('big-nav');
        }
    }

    function windowResized() {
        resizeLogo();
        headerResized();
    }

    $(window).resize(windowResized);

    $(document).ready(windowResized);

}(window.jQuery);
