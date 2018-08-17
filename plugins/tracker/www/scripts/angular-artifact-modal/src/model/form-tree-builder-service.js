import _ from "lodash";

export default FormTreeBuilderService;

FormTreeBuilderService.$inject = [];

function FormTreeBuilderService() {
    var self = this;
    self.buildFormTree = buildFormTree;

    const white_listed_field = [
        "fieldset",
        "column",
        "linebreak",
        "separator",
        "staticrichtext",
        "sb",
        "msb",
        "rb",
        "cb",
        "int",
        "string",
        "float",
        "text",
        "art_link",
        "burndown",
        "cross",
        "aid",
        "atid",
        "priority",
        "computed",
        "subby",
        "luby",
        "subon",
        "lud",
        "file",
        "perm",
        "date",
        "tbl"
    ];

    function buildFormTree(tracker) {
        var ordered_fields = _(tracker.structure)
            .map(function(structure_field) {
                return recursiveGetCompleteField(structure_field, tracker.fields);
            })
            .compact()
            .value();

        return ordered_fields;
    }

    function recursiveGetCompleteField(structure_field, all_fields) {
        var complete_field = _(all_fields).find({ field_id: structure_field.id });

        if (complete_field === undefined) {
            return false;
        }

        if (!white_listed_field.includes(complete_field.type)) {
            return false;
        }

        complete_field.template_url = "field-" + complete_field.type + ".tpl.html";

        if (structure_field.content !== null) {
            complete_field.content = _(structure_field.content)
                .map(function(sub_field) {
                    return recursiveGetCompleteField(sub_field, all_fields);
                })
                .compact()
                .value();
        }

        return complete_field;
    }
}
