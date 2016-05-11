angular
    .module('tuleap.frs')
    .directive('files', filesDirective);

function filesDirective() {
    return {
        restrict        : 'A',
        scope           : {},
        templateUrl     : 'release/files/files.tpl.html',
        controller      : 'FilesController as $ctrl',
        bindToController: true
    };
}
