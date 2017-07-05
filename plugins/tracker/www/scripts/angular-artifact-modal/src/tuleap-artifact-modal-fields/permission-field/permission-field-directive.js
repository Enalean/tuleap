angular
    .module('tuleap-artifact-modal-permission-field')
    .directive('tuleapArtifactModalPermissionField', tuleapArtifactModalPermissionFieldDirective);

tuleapArtifactModalPermissionFieldDirective.$inject = [];

function tuleapArtifactModalPermissionFieldDirective() {
    return {
        restrict        : 'EA',
        replace         : false,
        scope           : {
            field      : '=tuleapArtifactModalPermissionField',
            isDisabled : '&isDisabled',
            value_model: '=valueModel'
        },
        controller      : 'TuleapArtifactModalPermissionFieldController as permission_field',
        bindToController: true,
        templateUrl     : 'tuleap-artifact-modal-fields/permission-field/permission-field.tpl.html'
    };
}
