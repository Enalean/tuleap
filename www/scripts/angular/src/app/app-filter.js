export default InPropertiesFilter;

InPropertiesFilter.$inject = ['$filter'];

function InPropertiesFilter($filter) {
    return function(list, search, properties) {
        if (! search) {
            return list;
        }

        var keywords = search.split(' '),
            lookup   = '',
            result   = [];

        keywords.forEach(function(keyword) {
            properties.forEach(function(property) {
                var expression = {};
                expression[property] = keyword;
                lookup = $filter('filter')(list, expression);
                if (lookup.length > 0) {
                    result = result.concat(lookup);
                }
            });
        });

        return result;
    };
}
