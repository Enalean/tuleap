angular
    .module('tuleap-artifact-modal-computed-field')
    .directive('tuleapArtifactModalComputedField', TuleapArtifactModalComputedFieldDirective);

TuleapArtifactModalComputedFieldDirective.$inject = [];

function TuleapArtifactModalComputedFieldDirective() {
    return {
        restrict        : 'EA',
        replace         : false,
        scope           : {
            field      : '=tuleapArtifactModalComputedField',
            isDisabled : '&isDisabled',
            value_model: '=valueModel'
        },
        controller      : 'TuleapArtifactModalComputedFieldController as computed_field',
        bindToController: true,
        templateUrl     : 'tuleap-artifact-modal-fields/computed-field/computed-field.tpl.html'
    };
}
