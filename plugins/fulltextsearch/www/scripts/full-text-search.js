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
!(function ($) {

    $(document).ready(function() {
        handleFacets();
    });

    function handleFacets(){
        $('.facets').change(function() {
            var keywords = $('#words').attr('value');
            var facets = $('.facets').serialize();

            $.ajax({
              url: '/search/?type_of_search=fulltext&words='+keywords+'&'+facets,
              beforeSend: function() { $('.search-results').html('').addClass('loading'); }

            }).done(function(html) {
                $('.search-results').removeClass('loading');
                $('.search-results').html(html);

            }).fail(function() {
                $('.search-results').removeClass('loading');
                $('.search-results').html(codendi.locales.search.error);
            });
        });
    }
})(window.jQuery);