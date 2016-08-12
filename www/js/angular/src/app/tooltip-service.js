angular
    .module('tuleap.pull-request')
    .service('TooltipService', TooltipService);

TooltipService.$inject = [
    '$window',
    '$timeout'
];

function TooltipService(
    $window,
    $timeout
) {
    var self = this;

    self.setupTooltips = function() {
        $timeout(function() {
            $window.codendi.Tooltip.load($window.document.body);
        }, 0);
    };
}
