import _ from "lodash";

export default ComputedFieldValidateService;

ComputedFieldValidateService.$inject = [];

function ComputedFieldValidateService() {
    return {
        validateFieldValue: validateFieldValue
    };

    function validateFieldValue(field_value) {
        if (_.isUndefined(field_value)) {
            return null;
        }

        var is_autocomputed = Boolean(field_value.is_autocomputed);

        if (!is_autocomputed && _.isNull(field_value.manual_value)) {
            return null;
        }

        if (is_autocomputed) {
            delete field_value.manual_value;
        } else {
            delete field_value.is_autocomputed;
        }

        return removeUnusedAttributesComputed(field_value);
    }

    function removeUnusedAttributesComputed(field) {
        var attributes_to_keep = _.pick(field, function(property, key) {
            switch (key) {
                case "manual_value":
                case "field_id":
                case "is_autocomputed":
                    return !_.isUndefined(property);
                default:
                    return false;
            }
        });
        return attributes_to_keep;
    }
}
