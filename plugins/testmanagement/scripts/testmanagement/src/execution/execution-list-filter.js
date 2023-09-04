/*
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

// eslint-disable-next-line you-dont-need-lodash-underscore/map
import { compact, map, intersection } from "lodash-es";

export default ExecutionListFilter;

ExecutionListFilter.$inject = ["$filter"];

function ExecutionListFilter($filter) {
    return function (list, keywords, status) {
        setFocusableTestTab();

        var keyword_list = compact(keywords.split(" ")),
            status_list = compact(
                map(status, function (value, key) {
                    return value ? key : false;
                }),
            ),
            all_results = [];

        if (hasKeywords(keyword_list)) {
            all_results.push(keywordsMatcher(keyword_list, list));
        }

        if (hasStatus(status_list)) {
            all_results.push(statusMatcher(status_list, list));
        }

        all_results = intersection.apply(null, all_results);

        return [
            ...new Map(all_results.map((execution) => [execution.id, execution])).values(),
        ].sort((execution_a, execution_b) => {
            const execution_a_def_id = execution_a.definition.id;
            const execution_b_def_id = execution_b.definition.id;
            if (execution_a_def_id > execution_b_def_id) {
                return 1;
            }
            if (execution_a_def_id < execution_b_def_id) {
                return -1;
            }

            return 0;
        });
    };

    function hasKeywords(keyword_list) {
        return keyword_list.length > 0;
    }

    function hasStatus(status_list) {
        return status_list.length > 0;
    }

    function keywordsMatcher(keyword_list, list) {
        var result = [],
            lookup = "",
            properties = ["summary", "id", "category", "_uncategorized"];

        keyword_list.forEach(function (keyword) {
            properties.forEach(function (property) {
                var expression = {};
                expression[property] = keyword;

                lookup = $filter("filter")(list, { definition: expression });
                if (lookup.length > 0) {
                    result = result.concat(lookup);
                }
            });
        });

        return result;
    }

    function statusMatcher(status_list, list) {
        var result = [],
            lookup = "";

        status_list.forEach(function (status) {
            lookup = $filter("filter")(list, { status: status });
            if (lookup.length > 0) {
                result = result.concat(lookup);
            }
        });

        return result;
    }

    function setFocusableTestTab() {
        const previous_focusable_test_tab = document.querySelector(
            "[data-navigation-test-link][tabindex='0']",
        );
        const active_test_tab = document.querySelector("[data-navigation-test-link].active");
        const first_test_tab = document.querySelector("[data-navigation-test-link]");

        if (previous_focusable_test_tab) {
            previous_focusable_test_tab.setAttribute("tabindex", "-1");
        }

        if (active_test_tab) {
            active_test_tab.setAttribute("tabindex", "0");
            return;
        }

        if (first_test_tab) {
            first_test_tab.setAttribute("tabindex", "0");
        }
    }
}
