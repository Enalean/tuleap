(function () {
    angular
        .module('execution')
        .filter('ExecutionListFilter', ExecutionListFilter);

    ExecutionListFilter.$inject = ['$filter'];

    function ExecutionListFilter($filter) {
        return function(list, keywords, status, environment) {
            var keyword_list  = _.compact(keywords.split(' ')),
                status_list   = _.compact(_.map(status, function(value, key) { return (value) ? key : false; })),
                all_results   = [];

            if (! hasAtLeastOneFilter(keyword_list, status_list, environment)) {
                return list;
            }

            if (hasKeywords(keyword_list)) {
                all_results.push(keywordsMatcher(keyword_list, list));
            }

            if (hasStatus(status_list)) {
                all_results.push(statusMatcher(status_list, list));
            }

            if (hasEnvironment(environment)) {
                all_results.push(environmentMatcher(environment, list));
            }

            all_results = _.intersection.apply(null, all_results);

            return _.sortBy(_.uniq(all_results, getUniqKey), getSortByKey);
        };

        function getUniqKey(execution) {
            return execution.id;
        }

        function getSortByKey(execution) {
            return execution.definition.id;
        }

        function hasAtLeastOneFilter(keyword_list, status_list, environment) {
            return hasKeywords(keyword_list) || hasStatus(status_list) || hasEnvironment(environment);
        }

        function hasKeywords(keyword_list) {
            return keyword_list.length > 0;
        }

        function hasStatus(status_list) {
            return status_list.length > 0;
        }

        function hasEnvironment(environment) {
            return environment !== null;
        }

        function keywordsMatcher(keyword_list, list) {
            var result     = [],
                lookup     = '';
                properties = ['summary', 'id', 'category', '_uncategorized'];

            keyword_list.forEach(function(keyword) {
                properties.forEach(function(property) {
                    var expression = {};
                    expression[property] = keyword;

                    lookup = $filter('filter')(list, { definition: expression });
                    if (lookup.length > 0) {
                        result = result.concat(lookup);
                    }
                });
            });

            return result;
        }

        function statusMatcher(status_list, list) {
            var result = [],
                lookup = '';

            status_list.forEach(function(status) {
                lookup = $filter('filter')(list, { status: status });
                if (lookup.length > 0) {
                    result = result.concat(lookup);
                }
            });

            return result;
        }

        function environmentMatcher(environment, list) {
            return $filter('filter')(list, { environment: environment });
        }
    }
})();