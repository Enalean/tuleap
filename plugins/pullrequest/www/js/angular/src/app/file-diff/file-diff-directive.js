angular
    .module('tuleap.pull-request')
    .directive('fileDiff', FileDiffDirective);

function FileDiffDirective() {
    return {
        restrict        : 'A',
        scope           : {},
        templateUrl     : 'file-diff/file-diff.tpl.html',
        controller      : 'FileDiffController as diff',
        bindToController: true
    };
}
