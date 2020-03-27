/**
 * Copyright (c) Enalean, 2015-2017. All Rights Reserved.
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

/* global CKEDITOR:readonly tuleap:readonly */

!(function ($) {
    $(function () {
        var selector = $("#admin-headline-select-language");

        bindSwitchStatisticsOnHomePage();
        bindSwitchNewsOnHomePage();
        initCKEditor();
        selector.change(switchHeadline);

        function initCKEditor() {
            $('textarea[id^="admin-headline-"]').each(function () {
                var textarea_id = $(this).attr("id");

                CKEDITOR.replace(textarea_id, {
                    toolbar: tuleap.ckeditor.toolbar,
                });

                CKEDITOR.on("instanceReady", function () {
                    switchHeadline();
                });
            });
        }

        function switchHeadline() {
            var language_id = selector.val();

            $('div[id^="cke_admin-headline-"]').each(function () {
                var cke_instance = $(this);

                if (cke_instance.attr("id") === "cke_admin-headline-" + language_id) {
                    cke_instance.show().focus();
                } else {
                    cke_instance.hide();
                }
            });
        }

        function bindSwitchStatisticsOnHomePage() {
            $("#use_statistics_homepage").on("change", function () {
                $("#admin-homepage").submit();
            });
        }
        function bindSwitchNewsOnHomePage() {
            $("#use_news_homepage").on("change", function () {
                $("#admin-homepage").submit();
            });
        }
    });
})(window.jQuery);
