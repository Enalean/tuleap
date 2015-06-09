angular
    .module('modal')
    .factory('ModalModelFactory', ModalModelFactory);

function ModalModelFactory() {
    var awkward_fields_for_creation = ['aid', 'atid', 'lud', 'burndown', 'priority', 'subby', 'subon', 'computed', 'cross', 'file', 'tbl', 'perm'];

    return {
        createFromStructure: createFromStructure,
        reorderFieldsInGoodOrder: reorderFieldsInGoodOrder
    };


    /**
     * Completes artifact_values with the default values from the provided tracker structure. For every
     * field that is in structure but not in the artifact_values array, this will add its default value to the array or null;
     * @param  {array} artifact_values           An array of objects from the edited artifact { field_id, value|bind_value_ids } OR an empty array
     * @param  {TrackerRepresentation} structure The tracker structure as returned from the REST route
     * @return {Object}                          A map of objects indexed by field_id => { field_id, value|bind_value_ids }
     */
    function createFromStructure(artifact_values, structure) {
        var values = {};
        var indexed_values = _.indexBy(artifact_values, function(val) {
            return val.field_id;
        });

        var artifact_value;
        for (var i = 0, field; field = structure.fields[i]; i++) {
            artifact_value = indexed_values[field.field_id];

            if (_(awkward_fields_for_creation).contains(field.type)) {
                values[field.field_id] = {
                    field_id: field.field_id
                };
                if (artifact_value) {
                    // We attach the value to the structure to avoid submitting it
                    field.value = artifact_value.value;
                }
            // If the field already had a value in the artifact_values, we keep that value
            } else if (artifact_value) {
                values[field.field_id] = formatExistingValue(field, artifact_value);
            } else {
                values[field.field_id] = getDefaultValue(field);
            }
        }

        return values;
    }

    function formatExistingValue(field, artifact_value) {
        var value_obj = artifact_value;
        switch (field.type) {
            case "date":
                if (field.is_time_displayed) {
                    if (artifact_value.value) {
                        value_obj.value = moment(artifact_value.value, moment.ISO_8601).format("YYYY-MM-DD HH:mm:ss");
                    }
                } else {
                    if (artifact_value.value) {
                        value_obj.value = moment(artifact_value.value, moment.ISO_8601).format("YYYY-MM-DD");
                    }
                }
                break;
            case "cb":
                value_obj.bind_value_ids = mapCheckboxValues(field, artifact_value.bind_value_ids);
                break;
            case "rb":
                value_obj.bind_value_ids = !_.isEmpty(artifact_value.bind_value_ids) ? artifact_value.bind_value_ids : [100];
                break;
            default:
                break;
        }
        return value_obj;
    }

    function getDefaultValue(field) {
        var value_obj = {
            field_id: field.field_id
        };
        switch (field.type) {
            case "sb":
            case "msb":
                value_obj.bind_value_ids = (field.default_value) ? [].concat(field.default_value) : [];
                break;
            case "cb":
                value_obj.bind_value_ids = mapCheckboxValues(field, field.default_value);
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
                value_obj.format = "text";
                value_obj.value  = null;
                if (field.default_value) {
                    value_obj.format = field.default_value.format;
                    value_obj.value  = field.default_value.content;
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
        return value_obj;
    }

    function mapCheckboxValues(field, expected_values) {
        return _.map(field.values, function(possible_value) {
            return (_.contains(expected_values, possible_value.id)) ? possible_value.id : null;
        });
    }

    function reorderFieldsInGoodOrder(complete_tracker_structure) {
        var structure      = complete_tracker_structure.structure,
            ordered_fields = [];

        for (var i = 0; i < structure.length; i++) {
            ordered_fields.push(getCompleteField(structure[i], complete_tracker_structure.fields));
        }

        return _.compact(ordered_fields);
    }

    /**
     * Return a field with two additional attributes:
     *     - content     : {array} of fields
     *     - template_url: {string} angular tamplated used to render the field
     */
    function getCompleteField(structure_field, all_fields) {
        var complete_field = _(all_fields).find({ field_id: structure_field.id });

        if (_.contains(awkward_fields_for_creation, complete_field.type)) {
            return false;
        }

        complete_field.template_url = 'field-' + complete_field.type + '.tpl.html';

        if (structure_field.content != null) {
            complete_field.content = [];

            for (var i = 0; i < structure_field.content.length; i++) {
                complete_field.content.push(getCompleteField(structure_field.content[i], all_fields));
            }

            complete_field.content = _.compact(complete_field.content);
        }

        return complete_field;
    }

}
