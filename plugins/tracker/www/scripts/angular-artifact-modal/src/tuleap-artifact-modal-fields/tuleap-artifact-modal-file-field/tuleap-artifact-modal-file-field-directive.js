angular
    .module('tuleap-artifact-modal-file-field')
    .directive('tuleapArtifactModalFileField', TuleapArtifactModalFileFieldDirective);

TuleapArtifactModalFileFieldDirective.$inject = [];

function TuleapArtifactModalFileFieldDirective() {
    return {
        restrict        : 'EA',
        replace         : false,
        scope           : {
            field      : '=tuleapArtifactModalFileField',
            isDisabled : '&isDisabled',
            value_model: '=valueModel'
        },
        controller      : 'TuleapArtifactModalFileFieldController as file_field',
        bindToController: true,
        templateUrl     : 'tuleap-artifact-modal-fields/tuleap-artifact-modal-file-field/tuleap-artifact-modal-file-field.tpl.html'
    };
}
