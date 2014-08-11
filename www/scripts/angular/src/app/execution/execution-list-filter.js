angular
    .module('execution')
    .filter('ExecutionListFilter', ExecutionListFilter);

ExecutionListFilter.$inject = ['$filter'];

function ExecutionListFilter($filter) {
    return function(list, keywords, status, assignee) {
        var keyword_list  = _.compact(keywords.split(' ')),
            status_list   = _.compact(_.map(status, function(value, key) { return (value) ? key : false; })),
            all_results   = [];

        if (! hasAtLeastOneFilter(keyword_list, status_list, assignee)) {
            return list;
        }

        if (hasKeywords(keyword_list)) {
            all_results.push(keywordsMatcher(keyword_list, list));
        }

        if (hasStatus(status_list)) {
            all_results.push(statusMatcher(status_list, list));
        }

        if (hasAssignee(assignee)) {
            all_results.push(assigneeMatcher(assignee, list));
        }

        all_results = _.intersection.apply(null, all_results);

        return _.sortBy(_.uniq(all_results, getUniqKey), getSortByKey);
    };

    function getUniqKey(execution) {
        return execution.id;
    }

    function getSortByKey(execution) {
        return execution.test_def.id;
    }

    function hasAtLeastOneFilter(keyword_list, status_list, assignee) {
        return hasKeywords(keyword_list) || hasStatus(status_list) || hasAssignee(assignee);
    }

    function hasKeywords(keyword_list) {
        return keyword_list.length > 0;
    }

    function hasStatus(status_list) {
        return status_list.length > 0;
    }

    function hasAssignee(assignee) {
        return assignee !== null;
    }

    function keywordsMatcher(keyword_list, list) {
        var result = [],
            lookup = '';

        keyword_list.forEach(function(keyword) {
            lookup = $filter('filter')(list, {test_def: {summary: keyword, id: keyword, category: keyword}});
            if (lookup.length > 0) {
                result = result.concat(lookup);
            }
        });

        return result;
    }

    function statusMatcher(status_list, list) {
        var result = [],
            lookup = '';

        status_list.forEach(function(status) {
            lookup = $filter('filter')(list, {status: status});
            if (lookup.length > 0) {
                result = result.concat(lookup);
            }
        });

        return result;
    }

    function assigneeMatcher(assignee, list) {
        return $filter('filter')(list, {assigned_to: {id: assignee.id}});
    }
}