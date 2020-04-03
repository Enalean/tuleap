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

import _ from "lodash";

export default ExecutionListFilter;

ExecutionListFilter.$inject = ["$filter"];

function ExecutionListFilter($filter) {
    return function (list, keywords, status) {
        var keyword_list = _.compact(keywords.split(" ")),
            status_list = _.compact(
                //eslint-disable-next-line you-dont-need-lodash-underscore/map
                _.map(status, function (value, key) {
                    return value ? key : false;
                })
            ),
            all_results = [];

        if (!hasAtLeastOneFilter(keyword_list, status_list)) {
            return list;
        }

        if (hasKeywords(keyword_list)) {
            all_results.push(keywordsMatcher(keyword_list, list));
        }

        if (hasStatus(status_list)) {
            all_results.push(statusMatcher(status_list, list));
        }

        all_results = _.intersection.apply(null, all_results);

        //eslint-disable-next-line you-dont-need-lodash-underscore/uniq
        return _.sortBy(_.uniq(all_results, getUniqKey), getSortByKey);
    };

    function getUniqKey(execution) {
        return execution.id;
    }

    function getSortByKey(execution) {
        return execution.definition.id;
    }

    function hasAtLeastOneFilter(keyword_list, status_list) {
        return hasKeywords(keyword_list) || hasStatus(status_list);
    }

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
}
