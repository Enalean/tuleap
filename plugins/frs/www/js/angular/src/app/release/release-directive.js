angular
    .module('tuleap.frs')
    .directive('release', releaseDirective);

function releaseDirective() {
    return {
        restrict        : 'A',
        scope           : {},
        templateUrl     : 'release/release.tpl.html',
        controller      : 'ReleaseController as $ctrl',
        bindToController: true
    };
}
