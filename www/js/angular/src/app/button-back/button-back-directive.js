angular
    .module('tuleap.pull-request')
    .directive('buttonBack', ButtonBackDirective);

function ButtonBackDirective() {
    return {
        restrict        : 'E',
        scope           : {},
        templateUrl     : 'button-back/button-back.tpl.html',
        controller      : 'ButtonBackController as button_back_controller',
        bindToController: true
    };
}
