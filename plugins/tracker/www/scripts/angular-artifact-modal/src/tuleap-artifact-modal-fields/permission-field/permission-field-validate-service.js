angular
    .module('tuleap-artifact-modal-permission-field')
    .service('TuleapArtifactModalPermissionFieldValidateService', TuleapArtifactModalPermissionFieldValidateService);

function TuleapArtifactModalPermissionFieldValidateService() {
    return {
        validateFieldValue: validateFieldValue
    };

    function validateFieldValue(field_value) {
        if (_.isUndefined(field_value)) {
            return null;
        }

        return removeUnusedAttributesPermission(field_value);
    }

    function removeUnusedAttributesPermission(field) {
        return _.pick(field, ['field_id', 'value']);
    }
}
