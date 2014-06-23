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
            var type_of_search = $('input[name=type_of_search]').val();

            this.moveFacetsToSearchPane(type_of_search);
            switchSearchType();
            toggleFacets();
            decorRedirectedSearch();
            enableSearchMoreResults();
            resetSearchResults(type_of_search);
            highlightSearchCategory(type_of_search);
        },

        moveFacetsToSearchPane : function(type_of_search) {
            var search_pane_entry = $('a[data-search-type="'+type_of_search+'"]').parent();

            if ($('#search-results > ul:first-child').length > 0) {
                search_pane_entry.find('ul').remove();
                $('#search-results > ul:first-child').appendTo(search_pane_entry);
            }
        }
    };

    $(document).ready(function() {
        tuleap.search.init();
    });

    function switchSearchType() {
        $('[data-search-type]').click(function(e) {
            if ($(this).attr('href') == '#') {
                e.preventDefault();

                tuleap.search.facet = $(this);
                var type_of_search  = $(this).attr('data-search-type');

                resetSearchResults(type_of_search);
                searchFromSidebar(type_of_search, false);
                highlightSearchCategory(type_of_search);
            }
        });
    }

    function resetSearchResults(type_of_search){
        tuleap.search.type_of_search = type_of_search;
        tuleap.search.offset         = 0;
        codendi.feedback.clear();
    }

    function enableSearchMoreResults() {
        $('#search-more-button').unbind( "click" );
        $('#search-more-button').click(function() {
            tuleap.search.offset += parseInt($('input[name=number_of_page_results]').val());
            searchFromSidebar(tuleap.search.type_of_search, true);
        });
    }

    function highlightSearchCategory(type_of_search) {
        var highlighted_class = 'active';

        $('.search-type').each(function() {
            $(this).removeClass(highlighted_class);
            if ($(this).attr('data-search-type') == type_of_search) {
                $(this).addClass(highlighted_class);
            }
        })
    }

    function searchFromSidebar(type_of_search, append_to_results) {
        var keywords       = $('#words').val(),
            element        = tuleap.search.facet;

        (function beforeSend() {
            if (! append_to_results) {
                $('#search-results').html('');
            }
            $('#search-results').addClass('loading');
        })()
        $.getJSON(getSearchUrl(element, type_of_search, keywords)
        ).done(function(json) {
            if (append_to_results) {
                $('#search_results_list').append(json.html);
            } else {
                 $('#search-results').html(json.html);
            }
            if (json.has_more == true ) {
                $('#search-more-button').show();
            } else {
                $('#search-more-button').hide();
            }
            if (json.results_count == 0) {
                $('#no_more_results').show();
            } else {
                $('#no_more_results').hide();
            }
            tuleap.search.moveFacetsToSearchPane(type_of_search);
            enableSearchMoreResults();
        }).fail(function(error) {
              codendi.feedback.clear();
              codendi.feedback.log('error', codendi.locales.search.error + ' : ' + JSON.parse(error.responseText));
        }).always(function() {
              $('#search-results').removeClass('loading');
              $('.search-bar input[name="type_of_search"]').attr('value', type_of_search);
              resetAdditionnalInformations(type_of_search, element);
        });
    }

    function decorRedirectedSearch() {
        var icon_html = ' <i class="icon-external-link"></i>';

        $('a.search-type, a.sub-facets').each(function() {
            if ($(this).attr('href') != '#') {
                $(this).html($(this).html() + icon_html);
            }
        });
    }

    function getSearchUrl(element, type_of_search, keywords) {
        var offset = tuleap.search.offset,
            url    = '/search/?type_of_search='+type_of_search+
                '&words='+keywords+
                '&offset='+offset;

        return enrichUrlIfNeeded(element, type_of_search, url);
    }

    function enrichUrlIfNeeded(element, type_of_search, url) {
        if (type_of_search === 'tracker') {
            url += '&atid=' + getArtifactTypeId(element);
            url += '&group_id=' + getGroupId();
        }

        return url;
    }

    function getGroupId() {
        return $('.search-bar input[name="group_id"]').val();
    }

    function resetAdditionnalInformations(type_of_search, element) {
        purgeAdditionnalInformations();
        addAdditionnalInformations(type_of_search, element);
    }

    function purgeAdditionnalInformations() {
        $('.search-bar .input-append input[name="atid"]').remove();
    }

    function addAdditionnalInformations(type_of_search, element) {
        addArtifactTypeIdToSearchFieldIfNeeded(type_of_search, element);
    }

    function addArtifactTypeIdToSearchFieldIfNeeded(type_of_search, element) {
        if (type_of_search === 'tracker') {
            $('.search-bar .input-append').prepend(
                "<input name='atid' type='hidden' value='" + getArtifactTypeId(element) + "'>"
            );
        }
    }

    function getArtifactTypeId(element) {
        return $(element).attr('data-atid');
    }

    function toggleFacets() {
        $('.search-panes').on('click', '.search-type', function() {
            $(this).siblings('ul').toggle();
        });
    }

})(window.jQuery);
