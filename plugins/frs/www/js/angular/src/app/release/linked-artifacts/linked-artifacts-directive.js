angular
    .module('tuleap.frs')
    .directive('linkedArtifacts', linkedArtifactsDirective);

function linkedArtifactsDirective() {
    return {
        restrict        : 'A',
        scope           : {},
        templateUrl     : 'release/linked-artifacts/linked-artifacts.tpl.html',
        controller      : 'LinkedArtifactsController as $ctrl',
        bindToController: true
    };
}
