angular
    .module('tuleap.pull-request')
    .directive('diffLink', DiffLinkDirective);

function DiffLinkDirective() {
    return {
        restrict        : 'A',
        scope           : {},
        templateUrl     : 'file-diff/diff-link/diff-link.tpl.html',
        controller      : 'DiffLinkController as link',
        bindToController: true
    };
}
