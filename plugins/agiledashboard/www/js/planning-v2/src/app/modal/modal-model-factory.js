angular
    .module('modal')
    .factory('ModalModelFactory', ModalModelFactory);

function ModalModelFactory() {
    return {
        createFromStructure: createFromStructure
    };

    /**
     * Create the modal's model from a tracker's structure
     * @param  {TrackerRepresentation} structure The structure that is returned from the REST route
     * @return Object                            A map of objects { field_id, value|bind_value_ids} indexed by field_id
     */
    function createFromStructure(structure) {
        var values = {};

        for(var field, i = 0; field = structure.fields[i]; i++) {
            var value_obj = {
                field_id: field.field_id
            };

            switch (field.type) {
                case "sb":
                case "msb":
                    value_obj.bind_value_ids = (field.default_value) ? [].concat(field.default_value) : [];
                    break;
                case "cb":
                    value_obj.bind_value_ids = mapCheckboxValues(field);
                    break;
                case "rb":
                    value_obj.bind_value_ids = (field.default_value) ? field.default_value : [100];
                    break;
                case "int":
                    value_obj.value = (field.default_value) ? parseInt(field.default_value, 10) : null;
                    break;
                case "float":
                    value_obj.value = (field.default_value) ? parseFloat(field.default_value, 10) : null;
                    break;
                case "text":
                    value_obj.value = {
                        format  : "text",
                        content : undefined
                    };
                    if (field.default_value) {
                        value_obj.value.format = field.default_value.format;
                        value_obj.value.content = field.default_value.content;
                    }
                    break;
                case "string":
                case "date":
                    value_obj.value = (field.default_value) ? field.default_value : null;
                    break;
                case "art_link":
                    value_obj.unformatted_links = "";
                    value_obj.links = [ {id: ""} ];
                    break;
                default:
                    // Do nothing
                    break;
            }

            values[field.field_id] = value_obj;
        }

        return values;
    }

    function mapCheckboxValues(field) {
        return _.map(field.values, function(fieldValue) {
            return (_.contains(field.default_value, fieldValue.id)) ? fieldValue.id : null;
        });
    }
}
