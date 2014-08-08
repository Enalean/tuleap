angular
    .module('execution')
    .filter('ExecutionListFilter', ExecutionListFilter);

ExecutionListFilter.$inject = ['$filter'];

function ExecutionListFilter($filter) {
    return function(list, search) {
        if (! search) {
            return list;
        }

        var keywords = search.split(' '),
            lookup   = '',
            result   = [];

        keywords.forEach(function(keyword) {
            lookup = $filter('filter')(list, {'test_def': keyword});
            if (lookup.length > 0) {
                result = result.concat(lookup);
            }
        });

        return _.uniq(result, function(execution) { return execution.id; });
    };
}