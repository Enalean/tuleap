/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

var tuleap = tuleap || {};

!(function ($) {

    tuleap.search = {
        init : function() {
            switchSearchType();
        },

        moveFacetsToSearchPane : function(type_of_search) {
            var search_pane_entry = $('a[data-search-type="'+type_of_search+'"]').parent();

            if ($('.search-results > ul:first-child').length > 0) {
                search_pane_entry.find('ul').remove();
                $('.search-results > ul:first-child').appendTo(search_pane_entry);
            }
        }
    };

    $(document).ready(function() {
        tuleap.search.init();
    });

    function switchSearchType() {
        $('[data-search-type]').click(function() {
            var type_of_search = $(this).attr('data-search-type');
            var keywords = $('#words').attr('value');

            $.ajax({
                url: '/search/?type_of_search='+type_of_search+'&words='+keywords,
                beforeSend: function() { $('.search-results').html('').addClass('loading'); }
            }).done(function(html) {
                $('.search-results').html(html);
                tuleap.search.moveFacetsToSearchPane(type_of_search);
            }).fail(function() {
                $('.search-results').html(codendi.locales.search.error);
            }).always(function() {
                $('.search-results').removeClass('loading');
                $('.search-bar input[name="type_of_search"]').attr('value', type_of_search);
            });
        });
    }

})(window.jQuery);