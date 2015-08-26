angular
    .module('inproperties.filter', [
        'angularMoment'
    ])
    .filter('InPropertiesFilter', InPropertiesFilter);

InPropertiesFilter.$inject = [
    '$filter',
    'moment'
];

function InPropertiesFilter(
    $filter,
    moment
) {
    return function(list, terms) {
        if (! terms || terms === '') {
            return list;
        }

        var properties    = ['id', 'label', 'initial_effort'],
            keywords      = terms.split(' '),
            filtered_list = list;

        keywords.forEach(function(keyword) {
            var regexp = new RegExp(keyword, 'gi');

            filtered_list = $filter('filter')(filtered_list, function(item) {
                if (properties.some(function(property) {
                    return match(item[property]);
                })) {
                    return true;
                }

                if (item.card_fields && item.card_fields.some(matchCardFields)) {
                    return true;
                }

                if (item.parent && match(item.parent.label)) {
                    return true;
                }

                if (hasChildren(item)) {
                    var filtered_children = $filter('InPropertiesFilter')(item.children.data, terms);
                    return (! _.isEmpty(filtered_children));
                }

                return false;
            });

            function match(value) {
                return ("" + value).match(regexp);
            }

            function matchCardFields(card_field) {
                if (! card_field) {
                    return;
                }

                switch (card_field.type) {
                    case 'sb':
                    case 'rb':
                    case 'cb':
                    case 'tbl':
                    case 'msb':
                    case 'shared':
                        return card_field.values.some(function(value) {
                            if (typeof value.display_name !== 'undefined') {
                                return match(value.display_name);
                            }
                            return match(value.label);
                        });
                    case 'string':
                    case 'text':
                    case 'int':
                    case 'float':
                    case 'aid':
                    case 'atid':
                    case 'computed':
                    case 'priority':
                        return match(card_field.value);
                    case 'file':
                        return card_field.file_descriptions.some(function(file) {
                            return match(file.name);
                        });
                    case 'cross':
                        return card_field.value.some(function(link) {
                            return match(link.ref);
                        });
                    case 'perm':
                        return card_field.granted_groups.some(function(group) {
                            return match(group);
                        });
                    case 'subby':
                    case 'luby':
                        return match(card_field.value.display_name);
                    case 'date':
                    case 'lud':
                    case 'subon':
                        return match(moment(card_field.value).fromNow());
                }
            }
        });

        return filtered_list;
    };

    function hasChildren(item) {
        return (item.children && item.children.loaded && item.children.data);
    }
}
