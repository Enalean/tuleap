angular
    .module('modal')
    .factory('ModalValidateFactory', ModalValidateFactory);

function ModalValidateFactory() {
    return {
        validateArtifactFieldsValues : validateArtifactFieldsValues
    };

    function validateArtifactFieldsValues(field_values) {
        var filtered_values  = filterAtLeastOneAttribute(field_values);
        var validated_values = _.map(filtered_values, function(field) {
            if(_.isArray(field.bind_value_ids)) {
                // Validate the bind_value_ids
                field.bind_value_ids = _.without(field.bind_value_ids, null, undefined, "");
            } else if (field.links !== undefined) {
                field = buildLinks(field);
            }
            return field;
        });
        return validated_values;
    }

    function filterAtLeastOneAttribute(field_values) {
        var filtered_values = _.filter(field_values, function(field) {
            var value_present          = Boolean(field.value);
            var bind_value_ids_present = Boolean(field.bind_value_ids);
            var links_present          = Boolean(field.links);
            // This is a logical XOR: only one of those 3 attributes may be present
            // at the same time on a given field
            return (( value_present && !bind_value_ids_present && !links_present) ||
                    (!value_present &&  bind_value_ids_present && !links_present) ||
                    (!value_present && !bind_value_ids_present &&  links_present));
        });
        return filtered_values;
    }

    function buildLinks(field) {
        // Merge the text field with the selectbox to create the list of links
        if (_.isString(field.unformatted_links)) {
            var ids = field.unformatted_links.split(',');
            var objects = _.map(ids, function(link_id) {
                return { id: parseInt(link_id, 10) };
            });
            field.links             = field.links.concat(objects);
            field.unformatted_links = undefined;
        }
        // Then, filter out all the invalid id values (null, undefined, etc)
        field.links = _.filter(field.links, function(link) {
            return Boolean(link.id);
        });
        return field;
    }
}
