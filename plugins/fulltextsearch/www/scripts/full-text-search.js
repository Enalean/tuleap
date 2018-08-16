/**
 * Copyright (c) Enalean, 2014 - 2015. All Rights Reserved.
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
tuleap.search = tuleap.search || {};

!(function($) {
    tuleap.search.fulltext = {
        full_text_search: "fulltext",
        offset: 0,
        append_to_results: false,

        handleFulltextFacets: function(type_of_search) {
            if (type_of_search !== tuleap.search.fulltext.full_text_search) {
                return;
            }

            replaceSearchPanesByFacets();
            initFacets();
            enableFacets();

            function replaceSearchPanesByFacets() {
                if (tuleap.search.fulltext.append_to_results) {
                    return;
                }

                var facets_pane = $("#search-results > .search-pane"),
                    has_facets_in_result = facets_pane.length > 0,
                    already_has_facets =
                        $(".search-panes .search-pane-body.full-text-search").length > 0;

                if (!has_facets_in_result && !already_has_facets) {
                    $(".search-panes").remove();
                    $("#search-results").addClass("no-search-panes");
                } else if (has_facets_in_result) {
                    $(".search-pane").remove();
                    $(".search-panes").append(facets_pane);
                }
            }

            function initFacets() {
                $("select.select2").select2();
            }

            function enableFacets() {
                $(".search-pane .facet").on("change", function() {
                    tuleap.search.fulltext.offset = 0;
                    tuleap.search.fulltext.append_to_results = false;
                    tuleap.search.fulltext.updateResults();
                });
            }
        },

        updateResults: function() {
            var keywords = $("#words").val();

            (function beforeSend() {
                if (!tuleap.search.fulltext.append_to_results) {
                    $("#search-results").html("");
                }

                $("#search-results").addClass("loading");
            })();

            var url =
                "/search/?type_of_search=" +
                tuleap.search.fulltext.full_text_search +
                "&" +
                "words=" +
                keywords +
                "&" +
                "offset=" +
                tuleap.search.fulltext.offset +
                "&" +
                $(".search-pane .facet").serialize() +
                "&" +
                "group_id=" +
                $('.search-bar input[name="group_id"]').val();

            $.getJSON(url)
                .done(function(json) {
                    if (tuleap.search.fulltext.append_to_results) {
                        $("#search_results_list").append(json.html);
                    } else {
                        $("#search-results").html(json.html);
                    }

                    tuleap.search.fulltext.handleFulltextFacets(
                        tuleap.search.fulltext.full_text_search
                    );
                })
                .fail(function(error) {
                    codendi.feedback.clear();
                    codendi.feedback.log(
                        "error",
                        codendi.locales.search.error + " : " + error.responseText
                    );
                })
                .always(function() {
                    $("#search-results").removeClass("loading");
                    tuleap.search.fulltext.enableSearchMoreResults();
                    tuleap.search.fulltext.enableOneProjectSearchOptions();
                });
        },

        enableSearchMoreResults: function() {
            $("#search-more-button").unbind("click");
            $("#search-more-button").click(function() {
                tuleap.search.fulltext.offset += parseInt(
                    $("input[name=number_of_page_results]").val()
                );
                tuleap.search.fulltext.append_to_results = true;
                tuleap.search.fulltext.updateResults();
            });
        },

        enableOneProjectSearchOptions: function() {
            if (!$("#projects-selection").val()) {
                $(".one-project-only").attr("checked", false);
                $(".one-project-only").attr("disabled", true);
            } else if (isOnlyOneProjectSelected()) {
                $(".one-project-only").attr("disabled", false);
            } else {
                $(".one-project-only").attr("checked", false);
                $(".one-project-only").attr("disabled", true);
            }

            function isOnlyOneProjectSelected() {
                return (
                    $("#projects-selection").val().length === 1 &&
                    $("#projects-selection").val()[0] !== "user_projects_ids"
                );
            }
        }
    };

    $(document).ready(function() {
        $(".search-again").click(function() {
            if (
                tuleap.search.isPaneFullText() &&
                !tuleap.search.didUserClickOnDefaultSearch($(this))
            ) {
                tuleap.search.fulltext.updateResults();
                return false;
            }
        });
    });
})(window.jQuery);
