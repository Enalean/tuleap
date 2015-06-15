angular
    .module('modal')
    .factory('ModalModelFactory', ModalModelFactory);

function ModalModelFactory() {
    var awkward_fields_for_creation = ['aid', 'atid', 'lud', 'burndown', 'priority', 'subby', 'subon', 'computed', 'cross', 'file', 'tbl', 'perm'];
    var awkward_fields_for_edition  = ['tbl', 'perm'];

    return {
        createFromStructure: createFromStructure,
        reorderFieldsInGoodOrder: reorderFieldsInGoodOrder
    };

    /**
     * For every field in the tracker structure, creates a field object with the value from the given artifact
     * or the field's default value if there is no artifact and there is a default value.
     * @param  {Array} artifact_values            An array of objects from the edited artifact { field_id, value|bind_value_ids } OR an empty array
     * @param  {TrackerRepresentation} structure  The tracker structure as returned from the REST route
     * @return {Object}                           A map of objects indexed by field_id => { field_id, value|bind_value_ids }
     */
    function createFromStructure(artifact_values, structure) {
        var values = {};
        var indexed_values = _.indexBy(artifact_values, function(val) {
            return val.field_id;
        });

        var artifact_value;
        _.forEach(structure.fields, function(field) {
            artifact_value = indexed_values[field.field_id];

            field = transformStructure(field, artifact_value, structure.workflow);

            if (_(awkward_fields_for_creation).contains(field.type)) {
                values[field.field_id] = {
                    field_id: field.field_id,
                    type    : field.type
                };
            } else if (artifact_value) {
                values[field.field_id] = formatExistingValue(field, artifact_value);
            } else {
                values[field.field_id] = getDefaultValue(field);
            }
        });

        return values;
    }

    function transformStructure(field, artifact_value, workflow) {
        // We attach the value to the structure to avoid submitting it
        if (_(awkward_fields_for_creation).contains(field.type)) {
            field = augmentStructureField(field, artifact_value);
        } else {
            switch (field.type) {
                case "sb":
                    var selected_id;
                    if (artifact_value) {
                        selected_id = formatExistingValue(field, artifact_value).bind_value_ids[0];
                    } else {
                        selected_id = null;
                    }

                    field.values = filterWorkflowTransitions(workflow, field, selected_id);
                    field.values = displayUGroupI18NLabelIfAvailable(field);
                    break;
                case "msb":
                case "cb":
                case "rb":
                    field.values = displayUGroupI18NLabelIfAvailable(field);
                    break;
                default:
                    break;
            }
        }
        return field;
    }

    function displayUGroupI18NLabelIfAvailable(field) {
        _.map(field.values, function(value) {
            if (value.ugroup_reference !== undefined) {
                value.label = value.ugroup_reference.label;
            }
        });

        return field.values;
    }

    function augmentStructureField(field, artifact_value) {
        if (! artifact_value) {
            return field;
        }
        if (artifact_value.value) {
            field.value = artifact_value.value;
        } else if (artifact_value.file_descriptions) {
            field.file_descriptions = artifact_value.file_descriptions;
            _.map(field.file_descriptions, function(file) {
                file.display_as_image = /^image/.test(file.type);
                return file;
            });
        }
        return field;
    }

    function filterWorkflowTransitions(workflow, field, selected_id) {
        if (! workflow) {
            return field.values;
        }
        var workflow_id = workflow.field_id;
        var workflow_is_used = workflow.is_used;
        if (! workflow_is_used || (field.field_id !== workflow_id)) {
            return field.values;
        }

        var available_transition_ids = _(workflow.transitions).filter(function(transition) {
            return transition.from_id === selected_id;
        }).pluck("to_id").push(selected_id).compact().value();

        var selectable_ids = _.filter(field.values, function(value) {
            return _(available_transition_ids).contains(value.id);
        });
        return selectable_ids;
    }

    function formatExistingValue(field, artifact_value) {
        var value_obj         = artifact_value;
        value_obj.type        = field.type;
        value_obj.permissions = field.permissions;
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
            case "text":
                value_obj.value = {
                    content: artifact_value.value,
                    format: artifact_value.format
                };
                value_obj.format = undefined;
                break;
            default:
                break;
        }
        return value_obj;
    }

    function getDefaultValue(field) {
        var value_obj = {
            field_id   : field.field_id,
            type       : field.type,
            permissions: field.permissions
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
                value_obj.value  = {
                    content: null,
                    format: "text"
                };
                if (field.default_value) {
                    value_obj.value.format  = field.default_value.format;
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
        return value_obj;
    }

    function mapCheckboxValues(field, expected_values) {
        return _.map(field.values, function(possible_value) {
            return (_.contains(expected_values, possible_value.id)) ? possible_value.id : null;
        });
    }

    /**
     * Recreates the form's tree structure. Returns an object that contains container fields (e.g. fieldsets),
     * that themselves contain fields. Each field may have a "content" attribute that contains an array of
     * fields.
     * @param  {Object} complete_tracker_structure The tracker structure object as returned from the REST route.
     * @param  {boolean} creation_mode             True if the modal was opened to create a new artifact, false to edit an existing artifact
     * @return {Object}                            A tree-like Object representing the artifact's form.
     */
    function reorderFieldsInGoodOrder(complete_tracker_structure, creation_mode) {
        var structure      = complete_tracker_structure.structure,
            ordered_fields = [];

        for (var i = 0; i < structure.length; i++) {
            ordered_fields.push(getCompleteField(structure[i], complete_tracker_structure.fields, creation_mode));
        }

        return _.compact(ordered_fields);
    }

    /**
     * Returns a field with two additional attributes:
     *     - content     : {array} of fields
     *     - template_url: {string} angular tamplated used to render the field
     * @param  {Object} structure_field The field from the structure
     * @param  {Array} all_fields       The array containing all fields from the tracker structure
     * @param  {boolean} creation_mode  True if the modal was opened to create a new artifact, false to edit an existing artifact
     * @return {Object}                 The field with two added attributes: content and template_url
     */
    function getCompleteField(structure_field, all_fields, creation_mode) {
        var complete_field = _(all_fields).find({ field_id: structure_field.id });

        var excluded_fields = (creation_mode) ? awkward_fields_for_creation : awkward_fields_for_edition;
        if (_.contains(excluded_fields, complete_field.type)) {
            return false;
        }

        complete_field.template_url = 'field-' + complete_field.type + '.tpl.html';

        if (structure_field.content != null) {
            complete_field.content = [];

            for (var i = 0; i < structure_field.content.length; i++) {
                complete_field.content.push(getCompleteField(structure_field.content[i], all_fields, creation_mode));
            }

            complete_field.content = _.compact(complete_field.content);
        }

        return complete_field;
    }

}
