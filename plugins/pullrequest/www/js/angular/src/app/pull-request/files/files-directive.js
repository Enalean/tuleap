angular
    .module('tuleap.pull-request')
    .directive('files', FilesDirective);

function FilesDirective() {
    return {
        restrict        : 'A',
        scope           : {},
        templateUrl     : 'pull-request/files/files.tpl.html',
        controller      : 'FilesController as files',
        bindToController: true
    };
}
