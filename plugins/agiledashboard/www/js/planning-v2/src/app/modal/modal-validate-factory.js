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
            if (field.value !== undefined) {
                field = validateValue(field);
            } else if (_.isArray(field.bind_value_ids)) {
                field.bind_value_ids = _.compact(field.bind_value_ids);
            } else if (field.links !== undefined) {
                field = buildLinks(field);
            }
            return removeUnusedAttributes(field);
        });
        return validated_values;
    }

    function filterAtLeastOneAttribute(field_values) {
        var filtered_values = _.filter(field_values, function(field) {
            if (field !== undefined) {
                var value_defined          = (field.value !== undefined);
                var bind_value_ids_present = Boolean(field.bind_value_ids);
                var links_present          = Boolean(field.links);
                // This is a logical XOR: only one of those 3 attributes may be present
                // at the same time on a given field
                return (
                    ( value_defined && !bind_value_ids_present && !links_present) ||
                    (!value_defined &&  bind_value_ids_present && !links_present) ||
                    (!value_defined && !bind_value_ids_present &&  links_present)

                );
            } else {
                return false;
            }
        });
        return filtered_values;
    }

    function validateValue(field) {
        switch (field.type) {
            case "date":
            case "int":
            case "float":
                if (field.value === null) {
                    field.value = "";
                }
                break;

            default:
                break;
        }
        return field;
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

    function removeUnusedAttributes(field) {
        var field_obj = {};
        _.extend(field_obj, {
            bind_value_ids: field.bind_value_ids,
            field_id      : field.field_id,
            links         : field.links,
            value         : field.value
        });
        return field_obj;
    }
}
