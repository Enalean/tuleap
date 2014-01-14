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
    var width_collapsed = '45px';
    var width_expanded  = '200px';

    function getSidebarUserPreference() {
        return localStorage.getItem('sidebar-size');
    }

    function setSidebarUserPreference(new_width) {
        localStorage.setItem('sidebar-size', new_width);
    }

    function updateSidebarWidth(new_width, duration) {
        $('.sidebar-nav').animate({
            width:           new_width,
            maxnew_width:    new_width
        }, duration);
        $('.main').animate({
            marginLeft:      new_width
        }, duration);
    }

    function updateSidebarIcon(direction) {
        $('.sidebar-collapse').removeClass('icon-chevron-left icon-chevron-right').addClass('icon-chevron-' + direction);
    }

    function updateSidebarTitle(show_only_icon) {
        if (show_only_icon) {
            $('.project-title').css({
                display: 'none'
            });
            $('.nav-list').css({
                marginTop: '70px'
            });
        } else {
            $('.project-title').css({
                display: 'block'
            });
            $('.nav-list').css({
                marginTop: 'auto'
            });
        }
    }

    function updateSidebarServices(show_only_icon) {
        if (show_only_icon) {
            $('.sidebar-about').hide();
            $('.sidebar-nav li a > span').hide();
            $('.sidebar-nav li a').tooltip('enable');
        } else {
            $('.sidebar-nav li a > span').show();
            $('.sidebar-nav li a').tooltip('disable');
            $('.sidebar-about').show(150);
        }
    }

    function sidebarCollapseEvent(duration) {
        var current_size   = getSidebarUserPreference();
        var new_size       = width_expanded;
        var new_direction  = 'left';
        var show_only_icon = false;

        if (current_size == width_expanded) {
            new_size       = width_collapsed
            new_direction  = 'right';
            show_only_icon = true;
        }

        setSidebarUserPreference(new_size);

        updateSidebarTitle(show_only_icon);
        updateSidebarWidth(new_size, duration);
        updateSidebarIcon(new_direction);
        updateSidebarServices(show_only_icon);
    }

    $(document).ready(function() {
        var current_size = getSidebarUserPreference();

        $('.sidebar-nav').jScrollPane();

        if ($('.sidebar-nav').length > 0) {
            $('.sidebar-nav li a').tooltip({
                placement: 'right',
                container: 'body'
            });

            if (current_size == null || current_size == width_expanded) {
                updateSidebarTitle(false);
                updateSidebarWidth(width_expanded, 0);
                updateSidebarIcon('left');
                updateSidebarServices(false);
            } else {
                updateSidebarTitle(true);
                updateSidebarWidth(width_collapsed, 0);
                updateSidebarIcon('right');
                updateSidebarServices(true);
            }

            $('.sidebar-collapse').click(function() {
                sidebarCollapseEvent(150);
            });
        }
    });
}(window.jQuery);
